<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Academic Sessions') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex justify-content-between align-items-center mb-0 border-bottom-0 pb-0">
                <form method="GET" action="{{ route('academic.sessions.index') }}" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search sessions..." class="form-control me-2" style="max-width: 250px;">
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('academic.sessions.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Session
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
                            <th scope="col">Duration</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td class="fw-semibold">{{ $session->name }}</td>
                                <td>{{ $session->start_date->format('M Y') }} - {{ $session->end_date->format('M Y') }}</td>
                                <td>
                                    @if($session->is_current)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">Current</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Past/Upcoming</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('academic.sessions.edit', $session) }}" class="btn btn-sm btn-outline-primary" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('academic.sessions.destroy', $session) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No sessions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $sessions->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</x-app-layout>
