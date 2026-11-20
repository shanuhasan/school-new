<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\Guardian;
use App\Support\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuardianExportController extends Controller
{
    /**
     * One row per guardian–student link (a guardian with no linked student gets one row).
     * Column names match the guardian import template; student_name is informational and ignored on import.
     */
    private const COLUMNS = [
        'first_name' => 16, 'last_name' => 16, 'phone' => 15, 'email' => 26, 'occupation' => 18,
        'address' => 32, 'student_admission_number' => 22, 'student_name' => 22, 'relationship' => 13,
        'emergency_contact' => 17,
    ];

    public function __invoke(Request $request): BinaryFileResponse
    {
        $writer = new XlsxWriter(array_keys(self::COLUMNS), array_values(self::COLUMNS), 'Guardians');

        Guardian::query()
            ->filter($request->only('search'))
            ->with(['students' => fn ($q) => $q->orderBy('admission_number')])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->lazy(500)
            ->each(function (Guardian $g) use ($writer) {
                $base = [$g->first_name, $g->last_name, $g->phone, $g->email, $g->occupation, $g->address];

                if ($g->students->isEmpty()) {
                    $writer->addRow([...$base, null, null, null, null]);

                    return;
                }

                foreach ($g->students as $student) {
                    $writer->addRow([
                        ...$base,
                        $student->admission_number,
                        $student->full_name,
                        $student->pivot->relationship,
                        $student->pivot->is_emergency_contact ? 'yes' : 'no',
                    ]);
                }
            });

        $school = Str::slug(Tenant::getSchool()?->name ?? 'school');

        return response()
            ->download($writer->save(), "guardians-{$school}-" . now()->format('Y-m-d') . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store',
            ])
            ->deleteFileAfterSend();
    }
}
