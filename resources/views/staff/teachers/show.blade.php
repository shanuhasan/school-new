<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Teacher Details') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <h5 class="fw-bold">{{ $teacher->user->name }}</h5>
                    <p class="text-muted">{{ $teacher->user->email }}</p>
                </div>
                <div class="col-md-6 text-md-end mb-3">
                    <a href="{{ route('staff.teachers.edit', $teacher) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
                    <a href="{{ route('staff.teachers.index') }}" class="btn btn-light border ms-2">Back</a>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 ">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Employee ID</div>
                        <div class="col-sm-9">{{ $teacher->employee_id }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Phone</div>
                        <div class="col-sm-9">{{ $teacher->user->phone }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Department</div>
                        <div class="col-sm-9">{{ optional($teacher->department)->name }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Designation</div>
                        <div class="col-sm-9">{{ optional($teacher->designation)->name }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Joining Date</div>
                        <div class="col-sm-9">{{ optional($teacher->joining_date)->format('Y-m-d') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Status</div>
                        <div class="col-sm-9"><span class="badge bg-{{ $teacher->status == 'active' ? 'success' : ($teacher->status == 'on_leave' ? 'warning' : 'secondary') }}">{{ ucfirst(str_replace('_', ' ', $teacher->status)) }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-3 fw-medium">Qualification</div>
                        <div class="col-sm-9">{{ $teacher->qualification }}</div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-header  fw-bold">Assigned Subjects</div>
                        <ul class="list-group list-group-flush">
                            @forelse($teacher->subjects as $subject)
                                <li class="list-group-item">{{ $subject->name }}</li>
                            @empty
                                <li class="list-group-item text-muted">No subjects assigned</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-header  fw-bold">Assigned Classes</div>
                        <ul class="list-group list-group-flush">
                            @forelse($teacher->classes as $sClass)
                                <li class="list-group-item">{{ $sClass->name }}</li>
                            @empty
                                <li class="list-group-item text-muted">No classes assigned</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
