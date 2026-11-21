<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Student;
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
        'admission_number' => ['admission_number', false, 'Unique per school. Left blank → generated automatically'],
        'first_name' => ['first_name', false, 'Or use a single "name" column instead'],
        'last_name' => ['last_name', false, ''],
        'name' => ['name', false, 'Full name, if you don\'t split into first/last'],
        'date_of_birth' => ['date_of_birth', false, 'YYYY-MM-DD or DD-MM-YYYY (optional)'],
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
        'student_name' => 'name', 'full_name' => 'name', 'student' => 'name',
        'dob' => 'date_of_birth', 'birth_date' => 'date_of_birth', 'date_of_birth_dd_mm_yyyy' => 'date_of_birth',
        'sex' => 'gender', 'mobile' => 'phone', 'contact' => 'phone',
        'class_name' => 'class', 'grade' => 'class', 'section_name' => 'section',
        'roll_no' => 'roll_number', 'roll' => 'roll_number',
        'guardian_name' => 'guardian_first_name', 'parent_phone' => 'guardian_phone',
        'relationship' => 'guardian_relationship',
    ];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    public const RELATIONSHIPS = ['Father', 'Mother', 'Guardian', 'Uncle', 'Aunt', 'Grandparent', 'Other'];

    /* ------------------------------------------------------------------ */
    /*  Template                                                            */
    /* ------------------------------------------------------------------ */

    public function templateCsv(): string
    {
        // The template uses first_name/last_name; "name" is only accepted on import as an alternative.
        $labels = collect(self::COLUMNS)->except('name')->map(fn ($c) => $c[0])->values()->all();

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows non-English names correctly
        fputcsv($out, $labels);
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

        // The header may not be the first line (some exports add a title + blank rows). Find the
        // first line that looks like a header: it maps to at least two known columns.
        $headerIndex = null;
        $header = [];
        foreach (array_slice($raw, 0, 15, true) as $i => $cells) {
            $mapped = array_map(fn ($h) => $this->normaliseHeader((string) $h), $cells);
            $known = array_filter($mapped, fn ($k) => $k !== '' && array_key_exists($k, self::COLUMNS));
            if (count($known) >= 2) {
                $headerIndex = $i;
                $header = $mapped;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new RuntimeException('Could not find the column headings. Make sure the file has a header row (download the template to see the expected columns).');
        }

        // Must be able to build a name and have gender
        $hasName = in_array('name', $header, true) || (in_array('first_name', $header, true));
        if (! $hasName || ! in_array('gender', $header, true)) {
            throw new RuntimeException('The file needs a "name" (or "first_name") column and a "gender" column.');
        }

        $rows = [];
        foreach (array_slice($raw, $headerIndex + 1, null, true) as $i => $cells) {
            $row = [];
            foreach ($header as $col => $key) {
                if ($key !== '' && array_key_exists($key, self::COLUMNS)) {
                    // A later blank header column shouldn't wipe an earlier mapped one
                    $value = trim((string) ($cells[$col] ?? ''));
                    if (! isset($row[$key]) || $row[$key] === '') {
                        $row[$key] = $value;
                    }
                }
            }
            if (implode('', $row) === '') {
                continue; // skip blank lines
            }
            $rows[$i + 1] = $row; // 1-based spreadsheet row number
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
    public function validate(array $rows, int $schoolId, ?int $sessionId, bool $createMissing = false): array
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

        // Next number for auto-generated admission numbers (continue past the highest existing one)
        $admissionSeq = Student::withoutGlobalScope('school')->where('school_id', $schoolId)->count();
        $reserved = [];

        $seenInFile = [];
        $valid = [];
        $errors = [];

        foreach ($rows as $line => $row) {
            $rowErrors = [];

            // Name: use first/last, or split a single "name" column
            $first = trim($row['first_name'] ?? '');
            $last = trim($row['last_name'] ?? '');
            if ($first === '' && ($row['name'] ?? '') !== '') {
                $parts = preg_split('/\s+/', trim($row['name']));
                $first = (string) array_shift($parts);
                $last = implode(' ', $parts);
            }

            // Admission number: given, or auto-generated when blank/"NA"
            $adm = trim($row['admission_number'] ?? '');
            $autoAdmission = false;
            if ($adm === '' || mb_strtolower($adm) === 'na') {
                do {
                    $admissionSeq++;
                    $adm = 'ADM-' . str_pad((string) $admissionSeq, 5, '0', STR_PAD_LEFT);
                } while (isset($existingAdmissions[mb_strtolower($adm)]) || isset($reserved[mb_strtolower($adm)]));
                $reserved[mb_strtolower($adm)] = true;
                $autoAdmission = true;
            }

            $gender = mb_strtolower($row['gender'] ?? '');
            $gender = ['m' => 'male', 'f' => 'female', 'boy' => 'male', 'girl' => 'female'][$gender] ?? $gender;

            $data = [
                'admission_number' => $adm,
                'first_name' => $first,
                'last_name' => $last ?: null,
                'gender' => $gender,
                'status' => mb_strtolower(($row['status'] ?? '') ?: 'active'),
                'phone' => ($row['phone'] ?? '') ?: null,
                'email' => ($row['email'] ?? '') ?: null,
                'address' => ($row['address'] ?? '') ?: null,
                'blood_group' => ($row['blood_group'] ?? '') && strtoupper(str_replace(' ', '', $row['blood_group'])) !== 'NA'
                    ? strtoupper(str_replace(' ', '', $row['blood_group'])) : null,
            ];

            // Dates are optional; keep raw text when parsing fails so the validator flags it
            $rawDob = $row['date_of_birth'] ?? '';
            $rawAdm = $row['admission_date'] ?? '';
            $data['date_of_birth'] = $rawDob === '' ? null : ($this->parseDate($rawDob) ?? 'invalid');
            $data['admission_date'] = $rawAdm === '' ? now()->toDateString() : ($this->parseDate($rawAdm) ?? 'invalid');

            $v = Validator::make($data, [
                'admission_number' => 'required|string|max:50',
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'required|in:male,female,other',
                'status' => 'in:active,inactive',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'blood_group' => 'nullable|in:' . implode(',', self::BLOOD_GROUPS),
                'admission_date' => 'required|date',
            ], [
                'date_of_birth.date' => "date_of_birth \"{$rawDob}\" is not a valid date (use YYYY-MM-DD or DD-MM-YYYY).",
                'admission_date.date' => "admission_date \"{$rawAdm}\" is not a valid date (use YYYY-MM-DD or DD-MM-YYYY).",
                'first_name.required' => 'A name is required (name, or first_name).',
                'gender.required' => 'gender is required.',
                'email.email' => 'email is not a valid email address.',
                'gender.in' => 'gender must be male, female or other.',
                'status.in' => 'status must be active or inactive.',
                'blood_group.in' => 'blood_group must be one of ' . implode(', ', self::BLOOD_GROUPS) . '.',
                'date_of_birth.before' => 'date_of_birth must be in the past.',
            ]);
            foreach ($v->errors()->messages() as $messages) {
                $rowErrors[] = $messages[0];
            }

            // Duplicate admission numbers (only user-supplied ones; auto numbers are unique by design)
            if (! $autoAdmission) {
                $key = mb_strtolower($adm);
                if (isset($existingAdmissions[$key])) {
                    $rowErrors[] = "admission_number {$adm} already exists in this school.";
                } elseif (isset($seenInFile[$key])) {
                    $rowErrors[] = "admission_number {$adm} is repeated (also on row {$seenInFile[$key]}).";
                } else {
                    $seenInFile[$key] = $line;
                }
            }

            // Optional enrollment
            $enrollment = null;
            $className = trim($row['class'] ?? '');
            $sectionName = trim($row['section'] ?? '');
            if ($className !== '' && mb_strtolower($className) !== 'na') {
                $class = $classes->get(mb_strtolower($className));
                if (! $sessionId) {
                    $rowErrors[] = 'Choose an academic session to enrol students into a class.';
                } elseif (! $class && ! $createMissing) {
                    $rowErrors[] = "class \"{$className}\" does not exist. Create it first, or tick “Create missing classes & sections”.";
                } else {
                    $sectionGiven = $sectionName !== '' && mb_strtolower($sectionName) !== 'na';
                    $section = $class && $sectionGiven
                        ? $class->sections->first(fn ($s) => mb_strtolower(trim($s->name)) === mb_strtolower($sectionName))
                        : null;

                    if ($class && $sectionGiven && ! $section && ! $createMissing) {
                        $rowErrors[] = "section \"{$sectionName}\" does not belong to class \"{$class->name}\".";
                    } else {
                        $enrollment = [
                            'class_id' => $class?->id,
                            'class_name' => $class ? null : $className,          // create on import
                            'section_id' => $section?->id,
                            'section_name' => ($sectionGiven && ! $section) ? $sectionName : null,
                            'roll_number' => ($row['roll_number'] ?? '') ?: null,
                        ];
                    }
                }
            } elseif ($sectionName !== '' && mb_strtolower($sectionName) !== 'na') {
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
        return SpreadsheetReader::parseDate($value);
    }

    /* ------------------------------------------------------------------ */
    /*  Import                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{students: int, enrolled: int, guardians_created: int, guardians_linked: int, classes_created: int}
     */
    public function import(array $validRows, int $schoolId, ?int $sessionId): array
    {
        $summary = ['students' => 0, 'enrolled' => 0, 'guardians_created' => 0, 'guardians_linked' => 0, 'classes_created' => 0];

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

            // Caches for classes/sections created on the fly
            $classCache = SchoolClass::withoutGlobalScope('school')->where('school_id', $schoolId)->with('sections')->get()
                ->keyBy(fn ($c) => mb_strtolower(trim($c->name)));

            foreach ($validRows as $row) {
                $student = Student::create($row['student'] + ['school_id' => $schoolId]);
                $summary['students']++;

                if ($e = $row['enrollment']) {
                    if (! $sessionId) {
                        continue;
                    }

                    // Resolve (or create) the class
                    $classId = $e['class_id'];
                    if (! $classId && ! empty($e['class_name'])) {
                        $cached = $classCache->get(mb_strtolower(trim($e['class_name'])));
                        if ($cached) {
                            $classId = $cached->id;
                        } else {
                            $class = SchoolClass::create(['school_id' => $schoolId, 'name' => $e['class_name']]);
                            $classCache->put(mb_strtolower(trim($e['class_name'])), $class->setRelation('sections', collect()));
                            $classId = $class->id;
                            $summary['classes_created']++;
                        }
                    }

                    // Resolve (or create) the section
                    $sectionId = $e['section_id'];
                    if (! $sectionId && ! empty($e['section_name']) && $classId) {
                        $class = $classCache->first(fn ($c) => $c->id === $classId);
                        $section = $class?->sections->first(fn ($s) => mb_strtolower(trim($s->name)) === mb_strtolower(trim($e['section_name'])));
                        if (! $section) {
                            $section = \App\Models\Section::create(['school_class_id' => $classId, 'name' => $e['section_name']]);
                            $class?->sections->push($section);
                        }
                        $sectionId = $section->id;
                    }

                    if ($classId) {
                        $student->enrollments()->create([
                            'school_id' => $schoolId,
                            'academic_session_id' => $sessionId,
                            'school_class_id' => $classId,
                            'section_id' => $sectionId,
                            'roll_number' => $e['roll_number'],
                            'status' => 'active',
                        ]);
                        $summary['enrolled']++;
                    }
                }

                if ($g = $row['guardian']) {
                    $phoneKey = preg_replace('/\D+/', '', $g['phone']) ?: $g['phone'];
                    $guardian = $guardianCache[$phoneKey] ?? null;

                    if (! $guardian) {
                        $guardian = Guardian::create([
                            'school_id' => $schoolId,
                            'first_name' => $g['first_name'] ?: 'Guardian',
                            'last_name' => $g['last_name'] ?: ($row['student']['last_name'] ?? 'Guardian'),
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
