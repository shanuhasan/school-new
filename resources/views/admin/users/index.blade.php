@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $me = auth()->user();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Administration</span>
                    <h1 class="h3 mb-1">Users</h1>
                    <p>Sign in as another user to see what they see. You stay yourself until you choose one.</p>
                </div>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email…" class="form-control" style="max-width: 260px;">
                    <select name="role" class="form-select" style="max-width: 190px;">
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <span class="text-muted small">{{ $users->total() }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }}</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>School</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $isSuper = $user->roles->contains('name', 'Super Admin');
                                $isSelf = $user->is($me);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="initials">{{ $initials($user->name) }}</span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold">{{ $user->name }} @if($isSelf)<span class="badge bg-secondary">You</span>@endif</div>
                                            <div class="small text-muted text-truncate">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge {{ $role->name === 'Super Admin' ? 'bg-primary' : 'bg-secondary' }}">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td>{{ $user->currentSchool->name ?? '—' }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($isSelf)
                                        <span class="text-muted small">—</span>
                                    @elseif($isSuper)
                                        <span class="text-muted small" title="You cannot log in as another Super Admin">Super Admin</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.impersonate.start', $user) }}" class="d-inline"
                                              onsubmit="return confirm('Sign in as {{ addslashes($user->name) }}? You can return to your own account at any time.');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary btn-text">
                                                <i class="bi bi-box-arrow-in-right"></i> Login as
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</x-app-layout>
