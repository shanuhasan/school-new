<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div><h1 class="h3 mb-1">{{ __('Guardians') }}</h1></div>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex justify-content-between align-items-center mb-4 border-bottom-0 pb-0">
                <form action="{{ route('guardians.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search name, phone, email..." value="{{ request('search') }}" style="max-width: 250px;">
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('guardians.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Guardian
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Occupation</th>
                            <th>Students Linked</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guardians as $guardian)
                            <tr>
                                <td><div class="fw-bold">{{ $guardian->full_name }}</div></td>
                                <td>{{ $guardian->phone }}</td>
                                <td>{{ $guardian->email ?? '-' }}</td>
                                <td>{{ $guardian->occupation ?? '-' }}</td>
                                <td><span class="badge bg-info text-white">{{ $guardian->students()->count() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('guardians.edit', $guardian) }}" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('guardians.destroy', $guardian) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this guardian?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No guardians found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $guardians->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
