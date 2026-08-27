<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Staff') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom-0 pb-0 gap-2">
                <form method="GET" action="{{ route('staff.staffs.index') }}" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-control" style="max-width: 250px;">
                    <select name="department_id" class="form-select" style="max-width: 200px;">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('staff.staffs.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Staff
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name / Email</th>
                            <th scope="col">Employee ID</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffs as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $item->user->name }}</div>
                                    <div class="text-muted small">{{ $item->user->email }}</div>
                                </td>
                                <td>{{ $item->employee_id }}</td>
                                <td>{{ optional($item->department)->name }}</td>
                                <td><span class="badge bg-{{ $item->status == 'active' ? 'success' : ($item->status == 'on_leave' ? 'warning' : 'secondary') }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('staff.staffs.show', $item) }}" class="btn btn-sm btn-outline-info me-1">View</a>
                                    <a href="{{ route('staff.staffs.edit', $item) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                    <form action="{{ route('staff.staffs.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No staffs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $staffs->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-app-layout>
