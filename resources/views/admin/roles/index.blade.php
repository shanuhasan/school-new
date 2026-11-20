<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Administration</span>
                    <h1 class="h3 mb-1">Roles &amp; permissions</h1>
                    <p>Choose what each role can do. Changes apply immediately to everyone with that role.</p>
                </div>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td class="fw-semibold">{{ $role->name }}</td>
                                <td>
                                    @if($role->name === 'Super Admin')
                                        <span class="badge bg-primary">Full access (all areas)</span>
                                    @elseif($role->permissions_count === 0)
                                        <span class="text-muted">No access yet</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $role->permissions_count }} of {{ $permissionCount }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($role->name === 'Super Admin')
                                        <span class="text-muted small" title="Super Admin always has everything">Locked</span>
                                    @else
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary btn-text"><i class="bi bi-sliders"></i> Edit permissions</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0">
                <i class="bi bi-info-circle"></i> Access is always enforced on the server, so a role can never use a feature it isn't granted here — even by typing the address.
            </p>
        </div>
    </div>
</x-app-layout>
