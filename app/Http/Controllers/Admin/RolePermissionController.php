<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Super Admin UI to view and change what each role may do. Changes call syncPermissions(),
 * so they take effect immediately — no seeder editing.
 *
 * The "Super Admin" role is read-only here: it bypasses every check via Gate::before
 * (see AppServiceProvider), so toggling its permissions would do nothing.
 */
class RolePermissionController extends Controller
{
    /** Pretty group headings, in display order, keyed by the permission prefix before the dot. */
    private const GROUPS = [
        'students' => 'Students',
        'attendance' => 'Attendance',
        'timetable' => 'Timetable',
        'homework' => 'Homework',
        'exams' => 'Examinations',
        'marks' => 'Examinations',
        'results' => 'Examinations',
        'fees' => 'Fees',
        'academic' => 'Academic setup',
        'staff' => 'Staff & Teachers',
    ];

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('Super Admin'), 403);
    }

    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin($request);

        $roles = Role::withCount('permissions')->orderBy('name')->get();
        $permissionCount = Permission::count();

        return view('admin.roles.index', compact('roles', 'permissionCount'));
    }

    public function edit(Request $request, Role $role): View
    {
        $this->authorizeSuperAdmin($request);

        abort_if($role->name === 'Super Admin', 403, 'The Super Admin role always has full access and cannot be edited.');

        $groups = $this->groupedPermissions();
        $assigned = $role->permissions->pluck('name')->flip();

        return view('admin.roles.edit', compact('role', 'groups', 'assigned'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        abort_if($role->name === 'Super Admin', 403);

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        // Make the change visible immediately on the next request
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Log::info('role.permissions.updated', [
            'by' => $request->user()->id,
            'role' => $role->name,
            'count' => count($validated['permissions'] ?? []),
        ]);

        return redirect()->route('admin.roles.index')
            ->with('success', "Permissions for {$role->name} updated.");
    }

    /** @return array<string, \Illuminate\Support\Collection> group heading => permissions */
    private function groupedPermissions(): array
    {
        return Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $p) => self::GROUPS[Str::before($p->name, '.')] ?? 'Other')
            ->sortKeys()
            ->all();
    }
}
