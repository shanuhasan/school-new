<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin directory of login accounts, used to pick a user for "Login as".
 */
class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('Super Admin'), 403);

        $users = User::query()
            ->with(['roles:id,name', 'currentSchool:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->string('role'))))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return view('admin.users.index', compact('users', 'roles'));
    }
}
