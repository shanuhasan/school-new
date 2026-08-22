<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Subjects') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex justify-content-between align-items-center mb-0 border-bottom-0 pb-0">
                <form method="GET" action="{{ route('academic.subjects.index') }}" class="d-flex">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subjects..." class="form-control me-2" style="max-width: 250px;">
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('academic.subjects.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Subject
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
                            <th scope="col">Subject Name</th>
                            <th scope="col">Code</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td class="fw-semibold">{{ $subject->name }}</td>
                                <td><span class="badge bg-secondary">{{ $subject->code }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('academic.subjects.edit', $subject) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                    <form action="{{ route('academic.subjects.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No subjects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $subjects->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-app-layout>
