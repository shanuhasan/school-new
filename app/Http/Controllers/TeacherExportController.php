<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\Teacher;
use App\Support\XlsxWriter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherExportController extends Controller
{
    use AuthorizesRequests;

    /** Same column names as the teacher import template. */
    private const COLUMNS = [
        'employee_id' => 13, 'first_name' => 16, 'last_name' => 16, 'email' => 28, 'phone' => 15,
        'department' => 18, 'designation' => 20, 'joining_date' => 13, 'qualification' => 22,
        'status' => 11, 'subjects' => 30, 'classes' => 26,
    ];

    public function __invoke(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Teacher::class);

        $writer = new XlsxWriter(array_keys(self::COLUMNS), array_values(self::COLUMNS), 'Teachers');

        Teacher::forCurrentSchool()
            ->filter($request->only(['search', 'department_id']))
            ->with(['user', 'department', 'designation', 'subjects', 'classes'])
            ->orderBy('employee_id')
            ->orderBy('id')
            ->lazy(500)
            ->each(function (Teacher $t) use ($writer) {
                [$first, $last] = array_pad(explode(' ', (string) $t->user?->name, 2), 2, '');

                $writer->addRow([
                    $t->employee_id,
                    $first,
                    $last,
                    $t->user?->email,
                    $t->user?->phone,
                    $t->department?->name,
                    $t->designation?->name,
                    $t->joining_date,
                    $t->qualification,
                    $t->status,
                    $t->subjects->sortBy('name')->pluck('name')->implode(', '),
                    $t->classes->sortBy('name')->pluck('name')->implode(', '),
                ]);
            });

        $school = Str::slug(Tenant::getSchool()?->name ?? 'school');

        return response()
            ->download($writer->save(), "teachers-{$school}-" . now()->format('Y-m-d') . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store',
            ])
            ->deleteFileAfterSend();
    }
}
