<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\Student;
use App\Support\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentExportController extends Controller
{
    /**
     * Columns use the same names as the import template, so an export can be edited and re-imported
     * into another school/session. "session" is extra information and is ignored by the importer.
     */
    private const COLUMNS = [
        // header => width
        'admission_number' => 16, 'first_name' => 16, 'last_name' => 16, 'date_of_birth' => 13,
        'gender' => 9, 'admission_date' => 14, 'status' => 10, 'phone' => 15, 'email' => 26,
        'address' => 32, 'blood_group' => 11, 'class' => 12, 'section' => 9, 'roll_number' => 11,
        'session' => 12, 'guardian_first_name' => 18, 'guardian_last_name' => 18, 'guardian_phone' => 15,
        'guardian_email' => 26, 'guardian_relationship' => 20,
    ];

    public function __invoke(Request $request): BinaryFileResponse
    {
        $filters = $request->only(['search', 'class_id', 'section_id', 'session_id', 'status']);
        $sessionFilter = $filters['session_id'] ?? null;

        $writer = new XlsxWriter(array_keys(self::COLUMNS), array_values(self::COLUMNS), 'Students');

        Student::query()
            ->filter($filters)
            ->with([
                'enrollments' => fn ($q) => $q->with(['academicSession', 'schoolClass', 'section']),
                'guardians',
            ])
            ->orderBy('admission_number')
            ->lazy(500)
            ->each(function (Student $s) use ($writer, $sessionFilter, $filters) {
                $enrollment = $this->pickEnrollment($s, $sessionFilter, $filters['class_id'] ?? null);
                $guardian = $s->guardians->sortByDesc(fn ($g) => (int) $g->pivot->is_emergency_contact)->first();

                $writer->addRow([
                    $s->admission_number,
                    $s->first_name,
                    $s->last_name,
                    $s->date_of_birth,
                    $s->gender,
                    $s->admission_date,
                    $s->status,
                    $s->phone,
                    $s->email,
                    $s->address,
                    $s->blood_group,
                    $enrollment?->schoolClass?->name,
                    $enrollment?->section?->name,
                    $enrollment?->roll_number,
                    $enrollment?->academicSession?->name,
                    $guardian?->first_name,
                    $guardian?->last_name,
                    $guardian?->phone,
                    $guardian?->email,
                    $guardian?->pivot->relationship,
                ]);
            });

        $school = Str::slug(Tenant::getSchool()?->name ?? 'school');
        $filename = "students-{$school}-" . now()->format('Y-m-d') . '.xlsx';

        return response()
            ->download($writer->save(), $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store',
            ])
            ->deleteFileAfterSend();
    }

    /**
     * The enrollment shown for a student: the filtered session (and class) if given, otherwise the active
     * enrollment in the current session, otherwise the most recent one.
     */
    private function pickEnrollment(Student $s, $sessionId, $classId)
    {
        $list = $s->enrollments;

        if ($sessionId) {
            $list = $list->where('academic_session_id', (int) $sessionId);
        }
        if ($classId) {
            $list = $list->where('school_class_id', (int) $classId);
        }

        return $list->first(fn ($e) => $e->status === 'active' && $e->academicSession?->is_current)
            ?? $list->firstWhere('status', 'active')
            ?? $list->sortByDesc(fn ($e) => $e->academicSession?->start_date)->first();
    }
}
