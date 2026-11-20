<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Support\SpreadsheetReader;
use RuntimeException;

/**
 * Bulk student import from CSV or XLSX.
 *
 * Flow: read() -> validate() -> import(). Validation never writes; import() runs in one transaction.
 */
class StudentImportService
{
    public const MAX_ROWS = 2000;

    public function __construct(private SpreadsheetReader $reader)
    {
    }

    /** Column key => [label in template, required?, help text] */
    public const COLUMNS = [
        'admission_number' => ['admission_number', true, 'Unique per school, e.g. ADM-2045'],
        'first_name' => ['first_name', true, ''],
        'last_name' => ['last_name', true, ''],
        'date_of_birth' => ['date_of_birth', true, 'YYYY-MM-DD or DD-MM-YYYY'],
        'gender' => ['gender', true, 'male, female or other'],
        'admission_date' => ['admission_date', false, 'Defaults to today'],
        'status' => ['status', false, 'active or inactive (defaults to active)'],
        'phone' => ['phone', false, ''],
        'email' => ['email', false, ''],
        'address' => ['address', false, ''],
        'blood_group' => ['blood_group', false, 'A+, A-, B+, B-, AB+, AB-, O+, O-'],
        'class' => ['class', false, 'Class name exactly as set up, e.g. Grade 5'],
        'section' => ['section', false, 'Section of that class, e.g. A'],
        'roll_number' => ['roll_number', false, ''],
        'guardian_first_name' => ['guardian_first_name', false, 'Fill guardian columns to add a parent'],
        'guardian_last_name' => ['guardian_last_name', false, ''],
        'guardian_phone' => ['guardian_phone', false, 'Existing guardian with this phone is reused'],
        'guardian_email' => ['guardian_email', false, ''],
        'guardian_relationship' => ['guardian_relationship', false, 'Father, Mother, Guardian, Uncle, Aunt, Grandparent, Other'],
    ];

    private const ALIASES = [
        'admission_no' => 'admission_number', 'adm_no' => 'admission_number', 'admission' => 'admission_number',
        'dob' => 'date_of_birth', 'birth_date' => 'date_of_birth',
        'sex' => 'gender', 'mobile' => 'phone', 'class_name' => 'class', 'section_name' => 'section',
        'roll_no' => 'roll_number', 'father_name' => 'guardian_first_name', 'parent_phone' => 'guardian_phone',
        'relationship' => 'guardian_relationship',
    ];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    public const RELATIONSHIPS = ['Father', 'Mother', 'Guardian', 'Uncle', 'Aunt', 'Grandparent', 'Other'];

    /* ------------------------------------------------------------------ */
    /*  Template                                                            */
    /* ------------------------------------------------------------------ */

    public function templateCsv(): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows non-English names correctly
        fputcsv($out, array_column(self::COLUMNS, 0));
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /* ------------------------------------------------------------------ */
    /*  Reading                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<int, array<string, string>> rows keyed by spreadsheet row number (header = row 1)
     */
    public function read(UploadedFile $file): array
    {
        $raw = $this->reader->read($file);

        if (count($raw) < 1) {
            throw new RuntimeException('The file is empty.');
        }

        $header = array_map(fn ($h) => $this->normaliseHeader((string) $h), array_shift($raw));

        $missing = collect(self::COLUMNS)->filter(fn ($c) => $c[1])->keys()->diff($header);
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Missing required column(s): ' . $missing->implode(', ') . '. Please use the downloaded template.');
        }

        $rows = [];
        foreach ($raw as $i => $cells) {
            $row = [];
            foreach ($header as $col => $key) {
                if ($key !== '' && array_key_exists($key, self::COLUMNS)) {
                    $row[$key] = trim((string) ($cells[$col] ?? ''));
                }
            }
            if (implode('', $row) === '') {
                continue; // skip blank lines
            }
            $rows[$i + 2] = $row; // +2: 1-based and header row
        }

        if (count($rows) === 0) {
            throw new RuntimeException('The file has a header row but no students.');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('Please import at most ' . self::MAX_ROWS . ' students per file.');
        }

        return $rows;
    }

    private function normaliseHeader(string $h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
        $h = Str::of($h)->lower()->replace(['*', '(', ')'], '')->trim()->replaceMatches('/[\s\-\.\/]+/', '_')->trim('_')->toString();

        return self::ALIASES[$h] ?? $h;
    }

    /* ------------------------------------------------------------------ */
    /*  Validation                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{valid: array<int, array>, errors: array<int, array<int, string>>}
     */
    public function validate(array $rows, int $schoolId, ?int $sessionId): array
    {
        $existingAdmissions = Student::withoutGlobalScope('school')
            ->where('school_id', $schoolId)
            ->pluck('admission_number')
            ->map(fn ($a) => mb_strtolower($a))
            ->flip();

        $classes = SchoolClass::withoutGlobalScope('school')
            ->where('school_id', $schoolId)
            ->with('sections')
            ->get()
            ->keyBy(fn ($c) => mb_strtolower(trim($c->name)));

        $seenInFile = [];
        $valid = [];
        $errors = [];

        foreach ($rows as $line => $row) {
            $rowErrors = [];

            $data = [
                'admission_number' => $row['admission_number'] ?? '',
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'gender' => mb_strtolower($row['gender'] ?? ''),
                'status' => mb_strtolower(($row['status'] ?? '') ?: 'active'),
                'phone' => ($row['phone'] ?? '') ?: null,
                'email' => ($row['email'] ?? '') ?: null,
                'address' => ($row['address'] ?? '') ?: null,
                'blood_group' => ($row['blood_group'] ?? '') ? strtoupper(str_replace(' ', '', $row['blood_group'])) : null,
            ];
            $data['gender'] = ['m' => 'male', 'f' => 'female', 'boy' => 'male', 'girl' => 'female'][$data['gender']] ?? $data['gender'];

            // Keep the raw text when parsing fails so the validator reports it as an invalid date
            $rawDob = $row['date_of_birth'] ?? '';
            $rawAdm = $row['admission_date'] ?? '';
            $data['date_of_birth'] = $this->parseDate($rawDob) ?? ($rawDob === '' ? null : 'invalid');
            $data['admission_date'] = $rawAdm === '' ? now()->toDateString() : ($this->parseDate($rawAdm) ?? 'invalid');

            $v = Validator::make($data, [
                'admission_number' => 'required|string|max:50',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date|before:today',
                'gender' => 'required|in:male,female,other',
                'status' => 'in:active,inactive',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'blood_group' => 'nullable|in:' . implode(',', self::BLOOD_GROUPS),
                'admission_date' => 'required|date',
            ], [
                'date_of_birth.required' => 'date_of_birth is required.',
                'date_of_birth.date' => "date_of_birth \"{$rawDob}\" is not a valid date (use YYYY-MM-DD or DD-MM-YYYY).",
                'admission_date.date' => "admission_date \"{$rawAdm}\" is not a valid date (use YYYY-MM-DD or DD-MM-YYYY).",
                'admission_number.required' => 'admission_number is required.',
                'first_name.required' => 'first_name is required.',
                'last_name.required' => 'last_name is required.',
                'gender.required' => 'gender is required.',
                'email.email' => 'email is not a valid email address.',
                'gender.in' => 'gender must be male, female or other.',
                'status.in' => 'status must be active or inactive.',
                'blood_group.in' => 'blood_group must be one of ' . implode(', ', self::BLOOD_GROUPS) . '.',
                'date_of_birth.before' => 'date_of_birth must be in the past.',
            ]);
            // One message per field is enough (e.g. an invalid date shouldn't also say "must be in the past")
            foreach ($v->errors()->messages() as $messages) {
                $rowErrors[] = $messages[0];
            }

            $key = mb_strtolower($data['admission_number']);
            if ($key !== '') {
                if (isset($existingAdmissions[$key])) {
                    $rowErrors[] = "admission_number {$data['admission_number']} already exists in this school.";
                } elseif (isset($seenInFile[$key])) {
                    $rowErrors[] = "admission_number {$data['admission_number']} is repeated (also on row {$seenInFile[$key]}).";
                } else {
                    $seenInFile[$key] = $line;
                }
            }

            // Optional enrollment
            $enrollment = null;
            $className = $row['class'] ?? '';
            $sectionName = $row['section'] ?? '';
            if ($className !== '') {
                $class = $classes->get(mb_strtolower($className));
                if (! $class) {
                    $rowErrors[] = "class \"{$className}\" does not exist. Create it under Academic setup → Classes first.";
                } elseif (! $sessionId) {
                    $rowErrors[] = 'Choose an academic session to enrol students into a class.';
                } else {
                    $section = null;
                    if ($sectionName !== '') {
                        $section = $class->sections->first(fn ($s) => mb_strtolower(trim($s->name)) === mb_strtolower($sectionName));
                        if (! $section) {
                            $rowErrors[] = "section \"{$sectionName}\" does not belong to class \"{$class->name}\".";
                        }
                    }
                    $enrollment = [
                        'school_class_id' => $class->id,
                        'section_id' => $section?->id,
                        'roll_number' => ($row['roll_number'] ?? '') ?: null,
                    ];
                }
            } elseif ($sectionName !== '') {
                $rowErrors[] = 'section is filled but class is empty.';
            }

            // Optional guardian
            $guardian = null;
            $gFirst = $row['guardian_first_name'] ?? '';
            $gPhone = $row['guardian_phone'] ?? '';
            if ($gFirst !== '' || $gPhone !== '') {
                $relationship = Str::title($row['guardian_relationship'] ?? '');
                if ($gPhone === '') {
                    $rowErrors[] = 'guardian_phone is required when adding a guardian.';
                }
                if ($relationship === '') {
                    $rowErrors[] = 'guardian_relationship is required when adding a guardian.';
                } elseif (! in_array($relationship, self::RELATIONSHIPS, true)) {
                    $rowErrors[] = 'guardian_relationship must be one of ' . implode(', ', self::RELATIONSHIPS) . '.';
                }
                $gEmail = ($row['guardian_email'] ?? '') ?: null;
                if ($gEmail && ! filter_var($gEmail, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'guardian_email is not a valid email address.';
                }
                $guardian = [
                    'first_name' => $gFirst,
                    'last_name' => $row['guardian_last_name'] ?? '',
                    'phone' => $gPhone,
                    'email' => $gEmail,
                    'relationship' => $relationship,
                ];
            }

            if ($rowErrors) {
                $errors[$line] = array_values(array_unique($rowErrors));
            } else {
                $valid[$line] = ['student' => $data, 'enrollment' => $enrollment, 'guardian' => $guardian];
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * Accepts YYYY-MM-DD, DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY and Excel serial dates.
     */
    public function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 1000 && (float) $value < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y/m/d', 'Y-m-d H:i:s'] as $format) {
            $d = \DateTime::createFromFormat('!' . $format, $value);
            $err = \DateTime::getLastErrors();
            if ($d && (! $err || ($err['warning_count'] === 0 && $err['error_count'] === 0))) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  Import                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{students: int, enrolled: int, guardians_created: int, guardians_linked: int}
     */
    public function import(array $validRows, int $schoolId, ?int $sessionId): array
    {
        $summary = ['students' => 0, 'enrolled' => 0, 'guardians_created' => 0, 'guardians_linked' => 0];

        if ($sessionId) {
            AcademicSession::withoutGlobalScope('school')->where('school_id', $schoolId)->findOrFail($sessionId);
        }

        DB::transaction(function () use ($validRows, $schoolId, $sessionId, &$summary) {
            // Existing guardians by digits-only phone, so "+91 98765-00001" matches "919876500001"
            $guardianCache = Guardian::withoutGlobalScope('school')
                ->where('school_id', $schoolId)
                ->get()
                ->keyBy(fn ($g) => preg_replace('/\D+/', '', $g->phone) ?: $g->phone)
                ->all();

            foreach ($validRows as $row) {
                $student = Student::create($row['student'] + ['school_id' => $schoolId]);
                $summary['students']++;

                if ($row['enrollment'] && $sessionId) {
                    $student->enrollments()->create($row['enrollment'] + [
                        'school_id' => $schoolId,
                        'academic_session_id' => $sessionId,
                        'status' => 'active',
                    ]);
                    $summary['enrolled']++;
                }

                if ($g = $row['guardian']) {
                    $phoneKey = preg_replace('/\D+/', '', $g['phone']) ?: $g['phone'];
                    $guardian = $guardianCache[$phoneKey] ?? null;

                    if (! $guardian) {
                        $guardian = Guardian::create([
                            'school_id' => $schoolId,
                            'first_name' => $g['first_name'] ?: 'Guardian',
                            'last_name' => $g['last_name'] ?: $row['student']['last_name'],
                            'phone' => $g['phone'],
                            'email' => $g['email'],
                        ]);
                        $summary['guardians_created']++;
                    } else {
                        $summary['guardians_linked']++;
                    }
                    $guardianCache[$phoneKey] = $guardian;

                    $student->guardians()->syncWithoutDetaching([
                        $guardian->id => ['relationship' => $g['relationship'], 'is_emergency_contact' => false],
                    ]);
                }
            }
        });

        return $summary;
    }
}
