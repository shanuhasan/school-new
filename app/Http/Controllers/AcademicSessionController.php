<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AcademicSessionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', AcademicSession::class);

        $query = AcademicSession::forCurrentSchool();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $sessions = $query->orderByDesc('start_date')->paginate(10)->withQueryString();

        return view('academic.sessions.index', compact('sessions'));
    }

    public function create()
    {
        $this->authorize('create', AcademicSession::class);
        return view('academic.sessions.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', AcademicSession::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $validated['school_id'] = \App\Facades\Tenant::getSchoolId();
        $validated['is_current'] = $request->boolean('is_current');

        if ($validated['is_current']) {
            AcademicSession::forCurrentSchool()->update(['is_current' => false]);
        }

        AcademicSession::create($validated);

        return redirect()->route('academic.sessions.index')->with('success', 'Session created successfully.');
    }

    public function edit(AcademicSession $session)
    {
        $this->authorize('update', $session);
        return view('academic.sessions.edit', compact('session'));
    }

    public function update(Request $request, AcademicSession $session)
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        $validated['is_current'] = $request->boolean('is_current');

        if ($validated['is_current'] && !$session->is_current) {
            AcademicSession::forCurrentSchool()->where('id', '!=', $session->id)->update(['is_current' => false]);
        }

        $session->update($validated);

        return redirect()->route('academic.sessions.index')->with('success', 'Session updated successfully.');
    }

    public function destroy(AcademicSession $session)
    {
        $this->authorize('delete', $session);
        $session->delete();
        return redirect()->route('academic.sessions.index')->with('success', 'Session deleted successfully.');
    }
}
