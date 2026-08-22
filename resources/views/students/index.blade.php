<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div><h1 class="h3 mb-1">{{ __('Students') }}</h1></div>
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
                <form action="{{ route('students.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search name, admission no..." value="{{ request('search') }}" style="max-width: 250px;">
                    <select name="class_id" class="form-select" style="max-width: 150px;">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select" style="max-width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
                <a href="{{ route('students.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Student
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Adm. No</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Class (Current)</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $student->admission_number }}</span></td>
                                <td>
                                    @if($student->photo_path)
                                        <img src="{{ Storage::url($student->photo_path) }}" alt="{{ $student->full_name }}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                    @else
                                        <div class="avatar avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                            {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $student->full_name }}</div>
                                    <div class="small text-muted">{{ $student->gender }}</div>
                                </td>
                                <td>
                                    @php
                                        // Get the most recent active enrollment
                                        $enrollment = $student->enrollments()->where('status', 'active')->latest()->first();
                                    @endphp
                                    @if($enrollment)
                                        {{ $enrollment->schoolClass->name }} 
                                        {{ $enrollment->section ? ' - ' . $enrollment->section->name : '' }}
                                    @else
                                        <span class="text-muted">Not Enrolled</span>
                                    @endif
                                </td>
                                <td>{{ $student->phone ?? '-' }}</td>
                                <td>
                                    @if($student->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @elseif($student->status === 'inactive')
                                        <span class="badge bg-warning text-dark">Inactive</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($student->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info text-white" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('students.edit', $student) }}" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('students.destroy', $student) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this student?');">
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
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted mb-3"><i class="bi bi-people" style="font-size: 2rem;"></i></div>
                                    No students found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
