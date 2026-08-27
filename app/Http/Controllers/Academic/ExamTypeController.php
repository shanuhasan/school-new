<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\ExamType;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Facades\Tenant;

class ExamTypeController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ExamType::class);
        $examTypes = ExamType::where('school_id', Tenant::getSchoolId())->get();
        return view('academic.examinations.types.index', compact('examTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', ExamType::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $validated['school_id'] = Tenant::getSchoolId();
        ExamType::create($validated);
        return back()->with('success', 'Exam Type created successfully.');
    }

    public function destroy(ExamType $examType)
    {
        $this->authorize('delete', $examType);
        $examType->delete();
        return back()->with('success', 'Exam Type deleted successfully.');
    }
}
