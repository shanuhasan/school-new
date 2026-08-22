<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Facades\Tenant;

class StudentDocumentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Student $student)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120', // max 5MB
        ]);

        $file = $request->file('document');
        
        // Store on 'local' disk which is private (storage/app)
        $path = $file->store('student_documents/' . $student->id, 'local');

        $student->documents()->create([
            'school_id' => Tenant::getSchoolId(),
            'title' => $request->title,
            'file_path' => $path,
            'disk' => 'local',
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Download the specified resource securely.
     */
    public function download(StudentDocument $document)
    {
        // Authorization is implicitly handled by the route model binding and scoping,
        // but let's ensure it's from the same school
        if ($document->school_id !== Tenant::getSchoolId()) {
            abort(403, 'Unauthorized access to document.');
        }

        if (!Storage::disk($document->disk)->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk($document->disk)->download(
            $document->file_path, 
            $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentDocument $document)
    {
        // Check school
        if ($document->school_id !== Tenant::getSchoolId()) {
            abort(403, 'Unauthorized action.');
        }

        Storage::disk($document->disk)->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }
}
