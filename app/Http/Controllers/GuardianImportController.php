<?php

namespace App\Http\Controllers;

use App\Facades\Tenant;
use App\Services\GuardianImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class GuardianImportController extends Controller
{
    public function __construct(private GuardianImportService $importer)
    {
    }

    public function create(): View
    {
        return view('guardians.import', [
            'columns' => GuardianImportService::COLUMNS,
            'maxRows' => GuardianImportService::MAX_ROWS,
        ]);
    }

    public function template(): Response
    {
        return response($this->importer->templateCsv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="guardian-import-template.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120|extensions:csv,xlsx',
            'skip_invalid' => 'nullable|boolean',
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

        $result = $this->importer->validate($rows, $schoolId);
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

        return redirect()->route('guardians.import.create')->with('import_report', $report);
    }
}
