<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DesignationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Designation::class);

        $query = Designation::forCurrentSchool();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $designations = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('staff.designations.index', compact('designations'));
    }

    public function create()
    {
        $this->authorize('create', Designation::class);
        return view('staff.designations.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Designation::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['school_id'] = \App\Facades\Tenant::getSchoolId();
        Designation::create($validated);

        return redirect()->route('staff.designations.index')->with('success', 'Designation created successfully.');
    }

    public function edit(Designation $designation)
    {
        $this->authorize('update', $designation);
        return view('staff.designations.edit', compact('designation'));
    }

    public function update(Request $request, Designation $designation)
    {
        $this->authorize('update', $designation);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $designation->update($validated);

        return redirect()->route('staff.designations.index')->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation)
    {
        $this->authorize('delete', $designation);
        $designation->delete();
        return redirect()->route('staff.designations.index')->with('success', 'Designation deleted successfully.');
    }
}
