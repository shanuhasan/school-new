<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Teachers') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom-0 pb-0 gap-2">
                <form method="GET" action="{{ route('staff.teachers.index') }}" class="d-flex flex-wrap flex-md-nowrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-control" style="max-width: 250px;">
                    <select name="department_id" class="form-select" style="max-width: 200px;">
                        <option value="">All Departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <div class="d-flex gap-2">
                    <a href="{{ route('staff.teachers.export', request()->only(['search', 'department_id'])) }}" class="btn btn-light flex-fill"
                       title="{{ request()->anyFilled(['search', 'department_id']) ? 'Export the filtered teachers to Excel' : 'Export all teachers to Excel' }}">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export
                    </a>
                    <a href="{{ route('staff.teachers.import.create') }}" class="btn btn-light flex-fill">
                        <i class="bi bi-file-earmark-arrow-up"></i> Import
                    </a>
                    <a href="{{ route('staff.teachers.create') }}" class="btn btn-primary flex-fill">
                        <i class="bi bi-plus-lg me-1"></i> Add Teacher
                    </a>
                </div>
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
                        @forelse($teachers as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="initials initials-lg">{{ collect(preg_split('/\s+/', trim($item->user->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $item->user->name }}</div>
                                            <div class="text-muted small">{{ $item->user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>@if($item->employee_id)<span class="badge bg-secondary">{{ $item->employee_id }}</span>@else<span class="text-muted">—</span>@endif</td>
                                <td>{{ optional($item->department)->name }}</td>
                                <td><span class="badge bg-{{ $item->status == 'active' ? 'success' : ($item->status == 'on_leave' ? 'warning' : 'secondary') }}">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('staff.teachers.show', $item) }}" class="btn btn-sm btn-outline-info" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('staff.teachers.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('staff.teachers.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No teachers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $teachers->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-app-layout>
