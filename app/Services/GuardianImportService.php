<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\Student;
use App\Support\SpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bulk guardian import from CSV or XLSX.
 *
 * One row = one guardian, optionally linked to one or more students by admission number.
 * Rows sharing a phone number are treated as the same guardian; a phone already on file
 * re-uses that guardian (their details are not overwritten) and only adds the new links.
 */
class GuardianImportService
{
    public const MAX_ROWS = 2000;

    /** key => [required?, help] */
    public const COLUMNS = [
        'first_name' => [true, ''],
        'last_name' => [true, ''],
        'phone' => [true, 'Used to recognise the guardian. An existing guardian with this phone is reused.'],
        'email' => [false, ''],
        'occupation' => [false, ''],
        'address' => [false, ''],
        'student_admission_number' => [false, 'Child to link. Several children: separate with commas, e.g. ADM-1, ADM-2'],
        'relationship' => [false, 'Required when linking: Father, Mother, Guardian, Uncle, Aunt, Grandparent, Other'],
        'emergency_contact' => [false, 'yes or no (defaults to no)'],
    ];

    private const ALIASES = [
        'name' => 'first_name', 'mobile' => 'phone', 'phone_number' => 'phone', 'contact' => 'phone',
        'admission_number' => 'student_admission_number', 'student_admission_no' => 'student_admission_number',
        'admission_no' => 'student_admission_number', 'student' => 'student_admission_number',
        'relation' => 'relationship', 'is_emergency_contact' => 'emergency_contact', 'emergency' => 'emergency_contact',
    ];

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    public function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_keys(self::COLUMNS));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /** @return array<int, array<string, string>> rows keyed by spreadsheet row number */
    public function read(UploadedFile $file): array
    {
        $raw = $this->reader->read($file);
        if (count($raw) < 1) {
            throw new RuntimeException('The file is empty.');
        }

        $header = array_map(fn ($h) => SpreadsheetReader::normaliseHeader((string) $h, self::ALIASES), array_shift($raw));

        $missing = collect(self::COLUMNS)->filter(fn ($c) => $c[0])->keys()->diff($header);
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Missing required column(s): ' . $missing->implode(', ') . '. Please use the downloaded template.');
        }

        $rows = [];
        foreach ($raw as $i => $cells) {
            $row = [];
            foreach ($header as $col => $key) {
                if (array_key_exists($key, self::COLUMNS)) {
                    $row[$key] = trim((string) ($cells[$col] ?? ''));
                }
            }
            if (implode('', $row) !== '') {
                $rows[$i + 2] = $row;
            }
        }

        if (! $rows) {
            throw new RuntimeException('The file has a header row but no guardians.');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('Please import at most ' . self::MAX_ROWS . ' guardians per file.');
        }

        return $rows;
    }

    /** @return array{valid: array<int, array>, errors: array<int, array<int, string>>} */
    public function validate(array $rows, int $schoolId): array
    {
        $students = Student::withoutGlobalScope('school')
            ->where('school_id', $schoolId)
            ->get(['id', 'admission_number'])
            ->keyBy(fn ($s) => mb_strtolower($s->admission_number));

        $valid = [];
        $errors = [];
        $firstRowForPhone = [];   // phone => row number of first occurrence (for consistency messages)
        $nameForPhone = [];

        foreach ($rows as $line => $row) {
            $rowErrors = [];
            $data = [
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'phone' => $row['phone'] ?? '',
                'email' => ($row['email'] ?? '') ?: null,
                'occupation' => ($row['occupation'] ?? '') ?: null,
                'address' => ($row['address'] ?? '') ?: null,
            ];

            $v = Validator::make($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255',
                'occupation' => 'nullable|string|max:255',
            ], [
                'first_name.required' => 'first_name is required.',
                'last_name.required' => 'last_name is required.',
                'phone.required' => 'phone is required.',
                'phone.max' => 'phone may not be longer than 20 characters.',
                'email.email' => 'email is not a valid email address.',
            ]);
            foreach ($v->errors()->messages() as $messages) {
                $rowErrors[] = $messages[0];
            }

            // Same phone twice in the file must be the same person
            $phoneKey = $this->phoneKey($data['phone']);
            $fullName = mb_strtolower(trim($data['first_name'] . ' ' . $data['last_name']));
            if ($phoneKey !== '') {
                if (isset($nameForPhone[$phoneKey]) && $nameForPhone[$phoneKey] !== $fullName) {
                    $rowErrors[] = "phone {$data['phone']} is also used on row {$firstRowForPhone[$phoneKey]} for a different name.";
                } else {
                    $nameForPhone[$phoneKey] ??= $fullName;
                    $firstRowForPhone[$phoneKey] ??= $line;
                }
            }

            // Links to students
            $links = [];
            $admissions = array_filter(array_map('trim', preg_split('/[,;]/', $row['student_admission_number'] ?? '')));
            $relationship = Str::title(trim($row['relationship'] ?? ''));
            $emergency = $this->parseBool($row['emergency_contact'] ?? '');

            if ($emergency === null) {
                $rowErrors[] = 'emergency_contact must be yes or no.';
            }

            if ($admissions) {
                if ($relationship === '') {
                    $rowErrors[] = 'relationship is required when linking to a student.';
                } elseif (! in_array($relationship, StudentImportService::RELATIONSHIPS, true)) {
                    $rowErrors[] = 'relationship must be one of ' . implode(', ', StudentImportService::RELATIONSHIPS) . '.';
                }
                foreach ($admissions as $adm) {
                    $student = $students->get(mb_strtolower($adm));
                    if (! $student) {
                        $rowErrors[] = "No student with admission_number \"{$adm}\" in this school.";
                    } else {
                        $links[$student->id] = ['relationship' => $relationship, 'is_emergency_contact' => (bool) $emergency];
                    }
                }
            } elseif ($relationship !== '' && ! in_array($relationship, StudentImportService::RELATIONSHIPS, true)) {
                $rowErrors[] = 'relationship must be one of ' . implode(', ', StudentImportService::RELATIONSHIPS) . '.';
            }

            if ($rowErrors) {
                $errors[$line] = array_values(array_unique($rowErrors));
            } else {
                $valid[$line] = ['guardian' => $data, 'phone_key' => $phoneKey, 'links' => $links];
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /** @return array{created: int, existing: int, links: int} */
    public function import(array $validRows, int $schoolId): array
    {
        $summary = ['created' => 0, 'existing' => 0, 'links' => 0];

        DB::transaction(function () use ($validRows, $schoolId, &$summary) {
            // Existing guardians in this school, indexed by normalised phone
            $byPhone = Guardian::withoutGlobalScope('school')
                ->where('school_id', $schoolId)
                ->get()
                ->keyBy(fn ($g) => $this->phoneKey($g->phone));
            $seenInFile = [];

            foreach ($validRows as $row) {
                $key = $row['phone_key'];
                $guardian = $byPhone->get($key);

                if (! $guardian) {
                    $guardian = Guardian::create($row['guardian'] + ['school_id' => $schoolId]);
                    $byPhone->put($key, $guardian);
                    $summary['created']++;
                } elseif (! isset($seenInFile[$key])) {
                    $summary['existing']++;
                }
                $seenInFile[$key] = true;

                if ($row['links']) {
                    $already = $guardian->students()->pluck('students.id')->all();
                    $new = array_diff_key($row['links'], array_flip($already));
                    if ($new) {
                        $guardian->students()->attach($new);
                        $summary['links'] += count($new);
                    }
                }
            }
        });

        return $summary;
    }

    /** Digits only, so "+91 98765-00001" and "919876500001" match. */
    private function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : mb_strtolower(trim($phone));
    }

    private function parseBool(string $value): ?bool
    {
        $v = mb_strtolower(trim($value));

        return match (true) {
            $v === '' => false,
            in_array($v, ['yes', 'y', 'true', '1'], true) => true,
            in_array($v, ['no', 'n', 'false', '0'], true) => false,
            default => null,
        };
    }
}
