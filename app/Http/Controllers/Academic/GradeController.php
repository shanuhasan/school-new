<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Facades\Tenant;

class GradeController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Grade::class);
        $grades = Grade::where('school_id', Tenant::getSchoolId())->orderBy('min_percentage', 'desc')->get();
        return view('academic.examinations.grades.index', compact('grades'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Grade::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'min_percentage' => 'required|numeric|min:0|max:100',
            'max_percentage' => 'required|numeric|min:0|max:100|gte:min_percentage',
            'grade_point' => 'nullable|numeric|min:0',
            'description' => 'nullable|string'
        ]);
        $validated['school_id'] = Tenant::getSchoolId();
        Grade::create($validated);
        return back()->with('success', 'Grade created successfully.');
    }

    public function destroy(Grade $grade)
    {
        $this->authorize('delete', $grade);
        $grade->delete();
        return back()->with('success', 'Grade deleted successfully.');
    }
}
