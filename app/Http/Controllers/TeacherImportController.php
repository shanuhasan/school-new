<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Models\Department;
use App\Models\Designation;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\TeacherImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class TeacherImportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private TeacherImportService $importer)
    {
    }

    public function create(): View
    {
        $this->authorize('create', Teacher::class);

        return view('staff.teachers.import', [
            'columns' => TeacherImportService::COLUMNS,
            'maxRows' => TeacherImportService::MAX_ROWS,
            'departments' => Department::forCurrentSchool()->orderBy('name')->pluck('name'),
            'designations' => Designation::forCurrentSchool()->orderBy('name')->pluck('name'),
            'subjects' => Subject::forCurrentSchool()->orderBy('name')->get(['name', 'code']),
            'classes' => SchoolClass::forCurrentSchool()->orderBy('name')->pluck('name'),
        ]);
    }

    public function template(): Response
    {
        $this->authorize('create', Teacher::class);

        return response($this->importer->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="teacher-import-template.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Teacher::class);

        $request->validate([
            'file' => 'required|file|max:5120|extensions:csv,xlsx',
            'skip_invalid' => 'nullable|boolean',
            'create_missing' => 'nullable|boolean',
        ], [
            'file.extensions' => 'Upload a .csv or .xlsx file. For older .xls files, open them in Excel and save as .xlsx.',
            'file.max' => 'The file may not be larger than 5 MB.',
        ]);

        $schoolId = Tenant::getSchoolId();

        try {
            $rows = $this->importer->read($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()])->withInput();
        }

        $result = $this->importer->validate($rows, $schoolId, $request->boolean('create_missing'));
        $report = [
            'file' => $request->file('file')->getClientOriginalName(),
            'total' => count($rows),
            'error_count' => count($result['errors']),
            'valid_count' => count($result['valid']),
            'errors' => array_slice($result['errors'], 0, 300, true),
            'imported' => null,
        ];

        if (($report['error_count'] > 0 && ! $request->boolean('skip_invalid')) || $report['valid_count'] === 0) {
            return back()->withInput()->with('import_report', $report);
        }

        $report['imported'] = $this->importer->import($result['valid'], $schoolId);

        return redirect()->route('staff.teachers.import.create')->with('import_report', $report);
    }
}
