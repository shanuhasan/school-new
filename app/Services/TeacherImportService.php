<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Designation;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\SpreadsheetReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Bulk teacher import from CSV or XLSX.
 *
 * Each row creates a login account (User with the Teacher role, random password — teachers use
 * "Forgot password" to set their own) plus the Teacher record, and links subjects / classes by name.
 */
class TeacherImportService
{
    public const MAX_ROWS = 1000;
    public const STATUSES = ['active', 'inactive', 'on_leave', 'resigned'];

    /** key => [required?, help] */
    public const COLUMNS = [
        'employee_id' => [false, 'Unique per school, e.g. T-014'],
        'first_name' => [true, ''],
        'last_name' => [true, ''],
        'email' => [true, 'Used to sign in. Must not belong to another account.'],
        'phone' => [false, ''],
        'department' => [false, 'Department name, e.g. Science'],
        'designation' => [false, 'Designation name, e.g. Senior Teacher'],
        'joining_date' => [false, 'YYYY-MM-DD or DD-MM-YYYY'],
        'qualification' => [false, 'e.g. M.Sc, B.Ed'],
        'status' => [false, 'active, inactive, on_leave or resigned (defaults to active)'],
        'subjects' => [false, 'Subject names or codes, separated by commas, e.g. Mathematics, PHY'],
        'classes' => [false, 'Class names, separated by commas, e.g. Grade 5, Grade 6'],
    ];

    private const ALIASES = [
        'employee_no' => 'employee_id', 'emp_id' => 'employee_id', 'staff_id' => 'employee_id',
        'email_address' => 'email', 'mobile' => 'phone', 'dept' => 'department',
        'date_of_joining' => 'joining_date', 'doj' => 'joining_date', 'subject' => 'subjects', 'class' => 'classes',
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
            throw new RuntimeException('The file has a header row but no teachers.');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('Please import at most ' . self::MAX_ROWS . ' teachers per file.');
        }

        return $rows;
    }

    /**
     * @param bool $createMissing create departments / designations that don't exist yet instead of reporting them
     * @return array{valid: array<int, array>, errors: array<int, array<int, string>>}
     */
    public function validate(array $rows, int $schoolId, bool $createMissing = false): array
    {
        $lower = fn ($s) => mb_strtolower(trim((string) $s));

        $departments = Department::withoutGlobalScope('school')->where('school_id', $schoolId)->get()->keyBy(fn ($d) => $lower($d->name));
        $designations = Designation::withoutGlobalScope('school')->where('school_id', $schoolId)->get()->keyBy(fn ($d) => $lower($d->name));
        $classes = SchoolClass::withoutGlobalScope('school')->where('school_id', $schoolId)->get()->keyBy(fn ($c) => $lower($c->name));
        $subjects = Subject::withoutGlobalScope('school')->where('school_id', $schoolId)->get();
        $subjectByKey = $subjects->keyBy(fn ($s) => $lower($s->name))
            ->union($subjects->filter(fn ($s) => $s->code)->keyBy(fn ($s) => $lower($s->code)));

        // Emails are unique across all accounts (any school), so check only the addresses in this file
        $fileEmails = collect($rows)->pluck('email')->map($lower)->filter()->unique()->values();
        $existingEmails = $fileEmails->chunk(500)
            ->flatMap(fn ($chunk) => User::query()->whereIn(DB::raw('LOWER(email)'), $chunk->all())->pluck('email'))
            ->map($lower)
            ->flip();
        $existingEmployeeIds = Teacher::withoutGlobalScope('school')->where('school_id', $schoolId)
            ->whereNotNull('employee_id')->pluck('employee_id')->map($lower)->flip();

        $emailsInFile = [];
        $employeeIdsInFile = [];
        $valid = [];
        $errors = [];

        foreach ($rows as $line => $row) {
            $rowErrors = [];

            $status = str_replace([' ', '-'], '_', $lower($row['status'] ?? '')) ?: 'active';
            $rawJoin = $row['joining_date'] ?? '';
            $data = [
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'email' => $lower($row['email'] ?? ''),
                'phone' => ($row['phone'] ?? '') ?: null,
                'employee_id' => ($row['employee_id'] ?? '') ?: null,
                'joining_date' => $rawJoin === '' ? null : (SpreadsheetReader::parseDate($rawJoin) ?? 'invalid'),
                'qualification' => ($row['qualification'] ?? '') ?: null,
                'status' => $status,
            ];

            $v = Validator::make($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'employee_id' => 'nullable|string|max:50',
                'joining_date' => 'nullable|date',
                'status' => 'in:' . implode(',', self::STATUSES),
            ], [
                'first_name.required' => 'first_name is required.',
                'last_name.required' => 'last_name is required.',
                'email.required' => 'email is required (teachers sign in with it).',
                'email.email' => 'email is not a valid email address.',
                'joining_date.date' => "joining_date \"{$rawJoin}\" is not a valid date (use YYYY-MM-DD or DD-MM-YYYY).",
                'status.in' => 'status must be one of ' . implode(', ', self::STATUSES) . '.',
            ]);
            foreach ($v->errors()->messages() as $messages) {
                $rowErrors[] = $messages[0];
            }

            if ($data['email'] !== '') {
                if (isset($existingEmails[$data['email']])) {
                    $rowErrors[] = "email {$data['email']} already belongs to an account.";
                } elseif (isset($emailsInFile[$data['email']])) {
                    $rowErrors[] = "email {$data['email']} is repeated (also on row {$emailsInFile[$data['email']]}).";
                } else {
                    $emailsInFile[$data['email']] = $line;
                }
            }

            if ($data['employee_id']) {
                $key = $lower($data['employee_id']);
                if (isset($existingEmployeeIds[$key])) {
                    $rowErrors[] = "employee_id {$data['employee_id']} already exists in this school.";
                } elseif (isset($employeeIdsInFile[$key])) {
                    $rowErrors[] = "employee_id {$data['employee_id']} is repeated (also on row {$employeeIdsInFile[$key]}).";
                } else {
                    $employeeIdsInFile[$key] = $line;
                }
            }

            // Department / designation: matched by name, optionally created
            $department = $this->lookup($row['department'] ?? '', $departments, 'department', 'Staff & Teachers → Departments', $createMissing, $rowErrors);
            $designation = $this->lookup($row['designation'] ?? '', $designations, 'designation', 'Staff & Teachers → Designations', $createMissing, $rowErrors);

            // Subjects / classes: comma-separated lists, must exist
            $subjectIds = [];
            foreach ($this->splitList($row['subjects'] ?? '') as $name) {
                if ($s = $subjectByKey->get($lower($name))) {
                    $subjectIds[] = $s->id;
                } else {
                    $rowErrors[] = "subject \"{$name}\" does not exist in this school.";
                }
            }
            $classIds = [];
            foreach ($this->splitList($row['classes'] ?? '') as $name) {
                if ($c = $classes->get($lower($name))) {
                    $classIds[] = $c->id;
                } else {
                    $rowErrors[] = "class \"{$name}\" does not exist in this school.";
                }
            }

            if ($rowErrors) {
                $errors[$line] = array_values(array_unique($rowErrors));
            } else {
                $valid[$line] = $data + [
                    'department' => $department,       // int id | ['create' => name] | null
                    'designation' => $designation,
                    'subject_ids' => array_values(array_unique($subjectIds)),
                    'class_ids' => array_values(array_unique($classIds)),
                ];
            }
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /** @return array{teachers: int, departments_created: int, designations_created: int} */
    public function import(array $validRows, int $schoolId): array
    {
        $summary = ['teachers' => 0, 'departments_created' => 0, 'designations_created' => 0];
        $role = Role::findOrCreate('Teacher', 'web');

        DB::transaction(function () use ($validRows, $schoolId, $role, &$summary) {
            $created = ['department' => [], 'designation' => []];

            $resolve = function ($value, string $type) use ($schoolId, &$created, &$summary) {
                if (! is_array($value)) {
                    return $value; // id or null
                }
                $key = mb_strtolower($value['create']);
                if (! isset($created[$type][$key])) {
                    $model = $type === 'department' ? Department::class : Designation::class;
                    $created[$type][$key] = $model::create(['school_id' => $schoolId, 'name' => $value['create']])->id;
                    $summary[$type === 'department' ? 'departments_created' : 'designations_created']++;
                }

                return $created[$type][$key];
            };

            foreach ($validRows as $row) {
                $user = User::create([
                    'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'password' => Hash::make(Str::random(32)),
                    'current_school_id' => $schoolId,
                ]);
                $user->assignRole($role);
                $user->schools()->attach($schoolId);

                $teacher = Teacher::create([
                    'school_id' => $schoolId,
                    'user_id' => $user->id,
                    'department_id' => $resolve($row['department'], 'department'),
                    'designation_id' => $resolve($row['designation'], 'designation'),
                    'employee_id' => $row['employee_id'],
                    'joining_date' => $row['joining_date'],
                    'qualification' => $row['qualification'],
                    'status' => $row['status'],
                ]);

                if ($row['subject_ids']) {
                    $teacher->subjects()->attach($row['subject_ids']);
                }
                if ($row['class_ids']) {
                    $teacher->classes()->attach($row['class_ids']);
                }
                $summary['teachers']++;
            }
        });

        return $summary;
    }

    private function lookup(string $name, $collection, string $label, string $where, bool $createMissing, array &$rowErrors)
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        if ($found = $collection->get(mb_strtolower($name))) {
            return $found->id;
        }
        if ($createMissing) {
            return ['create' => $name];
        }
        $rowErrors[] = "{$label} \"{$name}\" does not exist. Add it under {$where}, or tick “Create missing departments and designations”.";

        return null;
    }

    private function splitList(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;]/', $value))));
    }
}
