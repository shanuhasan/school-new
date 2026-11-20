<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Classes') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex justify-content-between align-items-center mb-0 border-bottom-0 pb-0">
                <form method="GET" action="{{ route('academic.classes.index') }}" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search classes..." class="form-control me-2" style="max-width: 250px;">
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('academic.classes.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Class
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
                            <th scope="col">Level</th>
                            <th scope="col">Created At</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            <tr>
                                <td class="fw-semibold">{{ $class->name }}</td>
                                <td>{{ $class->level }}</td>
                                <td>{{ $class->created_at->format('Y-m-d') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('academic.classes.edit', $class) }}" class="btn btn-sm btn-outline-primary" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('academic.classes.destroy', $class) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No classes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $classes->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-app-layout>
