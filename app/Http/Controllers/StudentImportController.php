<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class StudentImportController extends Controller
{
    public function __construct(private StudentImportService $importer)
    {
    }

    public function create(): View
    {
        $sessions = AcademicSession::orderByDesc('is_current')->orderByDesc('start_date')->get();
        $classes = SchoolClass::with(['sections' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        return view('students.import', [
            'sessions' => $sessions,
            'classes' => $classes,
            'columns' => StudentImportService::COLUMNS,
            'maxRows' => StudentImportService::MAX_ROWS,
        ]);
    }

    public function template(): Response
    {
        return response($this->importer->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="student-import-template.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = Tenant::getSchoolId();

        $request->validate([
            // Check the extension, not the sniffed MIME type: some Excel builds produce .xlsx files detected as plain zip
            'file' => 'required|file|max:5120|extensions:csv,xlsx',
            'academic_session_id' => [
                'nullable',
                Rule::exists('academic_sessions', 'id')->where('school_id', $schoolId),
            ],
            'skip_invalid' => 'nullable|boolean',
            'create_missing' => 'nullable|boolean',
        ], [
            'file.extensions' => 'Upload a .csv or .xlsx file. For older .xls files, open them in Excel and save as .xlsx.',
            'file.max' => 'The file may not be larger than 5 MB.',
        ]);

        $sessionId = $request->integer('academic_session_id') ?: null;

        try {
            $rows = $this->importer->read($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        $result = $this->importer->validate($rows, $schoolId, $sessionId, $request->boolean('create_missing'));
        $errorCount = count($result['errors']);
        $validCount = count($result['valid']);

        $report = [
            'file' => $request->file('file')->getClientOriginalName(),
            'total' => count($rows),
            'error_count' => $errorCount,
            'valid_count' => $validCount,
            // Keep the session small; the first 300 problem rows are plenty to fix a file
            'errors' => array_slice($result['errors'], 0, 300, true),
        ];

        if ($errorCount > 0 && ! $request->boolean('skip_invalid')) {
            return back()
                ->withInput()
                ->with('import_report', $report + ['imported' => null]);
        }

        if ($validCount === 0) {
            return back()->withInput()->with('import_report', $report + ['imported' => null]);
        }

        $summary = $this->importer->import($result['valid'], $schoolId, $sessionId);

        return redirect()
            ->route('students.import.create')
            ->with('import_report', $report + ['imported' => $summary]);
    }
}
