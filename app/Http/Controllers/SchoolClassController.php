<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SchoolClassController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', SchoolClass::class);

        $query = SchoolClass::forCurrentSchool();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $classes = $query->orderBy('level')->paginate(10)->withQueryString();

        return view('academic.classes.index', compact('classes'));
    }

    public function create()
    {
        $this->authorize('create', SchoolClass::class);
        return view('academic.classes.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', SchoolClass::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|integer',
        ]);

        $validated['school_id'] = \App\Facades\Tenant::getSchoolId();

        SchoolClass::create($validated);

        return redirect()->route('academic.classes.index')->with('success', 'Class created successfully.');
    }

    public function show(SchoolClass $class)
    {
        $this->authorize('view', $class);

        $class->load([
            'sections' => fn ($q) => $q->orderBy('name'),
            'subjects' => fn ($q) => $q->orderBy('name'),
        ]);

        // Students are listed for the current session (or the latest one if none is marked current)
        $session = AcademicSession::forCurrentSchool()->where('is_current', true)->first()
            ?? AcademicSession::forCurrentSchool()->orderByDesc('start_date')->first();

        $enrollments = $session
            ? Enrollment::where('school_class_id', $class->id)
                ->where('academic_session_id', $session->id)
                ->where('status', 'active')
                ->with(['student', 'section'])
                ->get()
                ->filter(fn ($e) => $e->student)
                ->sortBy([
                    fn ($a, $b) => strnatcasecmp($a->section->name ?? '~', $b->section->name ?? '~'),
                    fn ($a, $b) => strnatcmp((string) $a->roll_number, (string) $b->roll_number),
                    fn ($a, $b) => strcasecmp($a->student->full_name, $b->student->full_name),
                ])
                ->values()
            : collect();

        $sectionCounts = $enrollments->countBy(fn ($e) => $e->section_id ?? 0);

        $teachers = Teacher::forCurrentSchool()
            ->whereHas('classes', fn ($q) => $q->whereKey($class->id))
            ->with(['user', 'subjects' => fn ($q) => $q->orderBy('name')])
            ->get()
            ->sortBy(fn ($t) => $t->user?->name)
            ->values();

        return view('academic.classes.show', compact('class', 'session', 'enrollments', 'sectionCounts', 'teachers'));
    }

    public function edit(SchoolClass $class)
    {
        $this->authorize('update', $class);

        $class->load('subjects');
        $subjects = Subject::forCurrentSchool()->orderBy('name')->get();

        return view('academic.classes.edit', compact('class', 'subjects'));
    }

    public function update(Request $request, SchoolClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'nullable|integer',
            'subjects' => 'nullable|array',
            'subjects.*' => ['integer', Rule::exists('subjects', 'id')->where('school_id', $class->school_id)->whereNull('deleted_at')],
            'optional' => 'nullable|array',
            'optional.*' => 'integer',
        ], [
            'subjects.*.exists' => 'One of the selected subjects does not belong to this school.',
        ]);

        DB::transaction(function () use ($class, $validated) {
            $class->update(['name' => $validated['name'], 'level' => $validated['level'] ?? null]);

            // Only touch subjects when the form sent the subjects section
            if (request()->boolean('subjects_present')) {
                $optional = array_map('intval', $validated['optional'] ?? []);
                $class->subjects()->sync(
                    collect($validated['subjects'] ?? [])
                        ->mapWithKeys(fn ($id) => [(int) $id => ['type' => in_array((int) $id, $optional, true) ? 'optional' : 'compulsory']])
                        ->all()
                );
            }
        });

        return redirect()->route('academic.classes.show', $class)->with('success', 'Class updated successfully.');
    }

    public function destroy(SchoolClass $class)
    {
        $this->authorize('delete', $class);
        $class->delete();
        return redirect()->route('academic.classes.index')->with('success', 'Class deleted successfully.');
    }

    public function getSections(SchoolClass $class)
    {
        // No authorization for basic dropdown fetching, or authorize viewAny
        if ($class->school_id !== \App\Facades\Tenant::getSchoolId()) {
            return response()->json([], 403);
        }
        return response()->json($class->sections()->select('id', 'name')->get());
    }
}
