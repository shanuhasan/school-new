<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div><h1 class="h3 mb-1">{{ __('Student Profile') }}</h1></div>
            </div>
            <div class="ms-auto">
                <a href="{{ route('students.edit', $student) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit Profile
                </a>
                <a href="{{ route('students.index') }}" class="btn btn-outline-secondary ms-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Sidebar Profile -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="panel">
                <div class="panel-body text-center p-4">
                    <div class="mb-3">
                        @if($student->photo_path)
                            <img src="{{ Storage::url($student->photo_path) }}" alt="{{ $student->full_name }}" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        @else
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:150px; height:150px; font-size: 3rem;">
                                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <h4 class="mb-1">{{ $student->full_name }}</h4>
                    <p class="text-muted mb-2">Adm No: {{ $student->admission_number }}</p>
                    <div class="mb-3">
                        @if($student->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($student->status) }}</span>
                        @endif
                    </div>
                    
                    <ul class="list-group list-group-flush text-start mt-4">
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Gender</span>
                            <span class="fw-medium">{{ ucfirst($student->gender) }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">DOB</span>
                            <span class="fw-medium">{{ $student->date_of_birth->format('d M Y') }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Blood Group</span>
                            <span class="fw-medium">{{ $student->blood_group ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Adm Date</span>
                            <span class="fw-medium">{{ $student->admission_date->format('d M Y') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="col-md-8 col-lg-9">
            <div class="panel">
                <div class="panel-header border-bottom-0 pb-0 pt-3">
                    <ul class="nav nav-tabs border-bottom" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">Information</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="guardians-tab" data-bs-toggle="tab" data-bs-target="#guardians" type="button" role="tab">Guardians</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="enrollments-tab" data-bs-toggle="tab" data-bs-target="#enrollments" type="button" role="tab">Enrollments</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">Documents</button>
                        </li>
                    </ul>
                </div>
                <div class="panel-body p-4">
                    <div class="tab-content" id="profileTabsContent">
                        
                        <!-- Information Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <h5 class="mb-3 text-primary">Contact Information</h5>
                            <div class="row mb-4">
                                <div class="col-sm-3 text-muted">Phone</div>
                                <div class="col-sm-9 fw-medium">{{ $student->phone ?? 'Not provided' }}</div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-sm-3 text-muted">Email</div>
                                <div class="col-sm-9 fw-medium">{{ $student->email ?? 'Not provided' }}</div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-sm-3 text-muted">Address</div>
                                <div class="col-sm-9 fw-medium">{{ $student->address ?? 'Not provided' }}</div>
                            </div>
                        </div>

                        <!-- Guardians Tab -->
                        <div class="tab-pane fade" id="guardians" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 text-primary">Parents & Guardians</h5>
                                <a href="{{ route('guardians.create', ['student_id' => $student->id]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg"></i> Add Guardian
                                </a>
                            </div>
                            
                            @if($student->guardians->count() > 0)
                                <div class="row g-3">
                                    @foreach($student->guardians as $guardian)
                                        <div class="col-md-6">
                                            <div class="card border border-light-subtle h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <h6 class="mb-0 fw-bold">{{ $guardian->full_name }}</h6>
                                                        @if($guardian->pivot->is_emergency_contact)
                                                            <span class="badge bg-danger">Emergency</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-primary small mb-2">{{ $guardian->pivot->relationship }}</p>
                                                    <div class="small mb-1"><i class="bi bi-telephone text-muted me-2"></i> {{ $guardian->phone }}</div>
                                                    @if($guardian->email)
                                                        <div class="small mb-1"><i class="bi bi-envelope text-muted me-2"></i> {{ $guardian->email }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p>No guardians attached to this student.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Enrollments Tab -->
                        <div class="tab-pane fade" id="enrollments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 text-primary">Enrollment History</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#enrollModal">
                                    <i class="bi bi-plus-lg"></i> New Enrollment
                                </button>
                            </div>

                            @if($student->enrollments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Session</th>
                                                <th>Class & Section</th>
                                                <th>Roll No</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($student->enrollments as $enrollment)
                                                <tr>
                                                    <td>{{ $enrollment->academicSession->name }}</td>
                                                    <td>
                                                        {{ $enrollment->schoolClass->name }}
                                                        {{ $enrollment->section ? ' - ' . $enrollment->section->name : '' }}
                                                    </td>
                                                    <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                                    <td>
                                                        @if($enrollment->status === 'active')
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ ucfirst($enrollment->status) }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <form action="{{ route('students.enrollments.destroy', [$student, $enrollment]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove enrollment?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p>No enrollments found for this student.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Documents Tab -->
                        <div class="tab-pane fade" id="documents" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0 text-primary">Student Documents</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#documentModal">
                                    <i class="bi bi-upload"></i> Upload Document
                                </button>
                            </div>

                            @if($student->documents->count() > 0)
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Document Title</th>
                                                <th>Uploaded</th>
                                                <th>Size</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($student->documents as $doc)
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                                        {{ $doc->title }}
                                                    </td>
                                                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                                                    <td>{{ number_format($doc->file_size / 1024, 2) }} KB</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-sm btn-info text-white" target="_blank">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                        <form action="{{ route('documents.destroy', $doc) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this document forever?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <p>No documents uploaded yet.</p>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enroll Modal -->
    <div class="modal fade" id="enrollModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('students.enrollments.store', $student) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Academic Session <span class="text-danger">*</span></label>
                            <select name="academic_session_id" class="form-select" required>
                                <option value="">Select Session</option>
                                @foreach(\App\Models\AcademicSession::all() as $session)
                                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Class <span class="text-danger">*</span></label>
                            <select name="school_class_id" class="form-select" required>
                                <option value="">Select Class</option>
                                @foreach(\App\Models\SchoolClass::all() as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section</label>
                            <select name="section_id" class="form-select">
                                <option value="">No Section</option>
                                @foreach(\App\Models\Section::all() as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Roll Number</label>
                            <input type="text" name="roll_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="promoted">Promoted</option>
                                <option value="dropped">Dropped</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Enroll Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Document Modal -->
    <div class="modal fade" id="documentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('students.documents.store', $student) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Document Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Birth Certificate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" name="document" class="form-control" required>
                            <div class="form-text">Accepted formats: PDF, JPG, PNG, DOCX. Max: 5MB.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
