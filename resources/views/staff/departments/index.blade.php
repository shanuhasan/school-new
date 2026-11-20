<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Departments') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom-0 pb-0 gap-2">
                <form method="GET" action="{{ route('staff.departments.index') }}" class="d-flex flex-wrap flex-md-nowrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-control" style="max-width: 250px;">
                    
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('staff.departments.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Department
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
                            <th scope="col">Name</th>
                            <th scope="col">Description</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->name }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-end">
                                    
                                    <a href="{{ route('staff.departments.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('staff.departments.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No departments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $departments->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-app-layout>
