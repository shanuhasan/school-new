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
                        <li class="list-group-item bg-transparent border-secondary px-0 d-flex justify-content-between">
                            <span class="text-secondary">Gender</span>
                            <span class="fw-medium text-light">{{ ucfirst($student->gender) }}</span>
                        </li>
                        <li class="list-group-item bg-transparent border-secondary px-0 d-flex justify-content-between">
                            <span class="text-secondary">DOB</span>
                            <span class="fw-medium text-light">{{ $student->date_of_birth->format('d M Y') }}</span>
                        </li>
                        <li class="list-group-item bg-transparent border-secondary px-0 d-flex justify-content-between">
                            <span class="text-secondary">Blood Group</span>
                            <span class="fw-medium text-light">{{ $student->blood_group ?? '-' }}</span>
                        </li>
                        <li class="list-group-item bg-transparent border-secondary px-0 border-bottom-0 d-flex justify-content-between">
                            <span class="text-secondary">Adm Date</span>
                            <span class="fw-medium text-light">{{ $student->admission_date->format('d M Y') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="col-md-8 col-lg-9">
            <div class="panel">
                <div class="panel-header border-bottom-0 pb-0 pt-3">
                    <ul class="nav nav-pills border-bottom border-secondary pb-3" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill px-4" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">Information</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-4" id="guardians-tab" data-bs-toggle="tab" data-bs-target="#guardians" type="button" role="tab">Guardians</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-4" id="enrollments-tab" data-bs-toggle="tab" data-bs-target="#enrollments" type="button" role="tab">Enrollments</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill px-4" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab">Documents</button>
                        </li>
                    </ul>
                </div>
                <div class="panel-body p-4">
                    <div class="tab-content" id="profileTabsContent">
                        
                        <!-- Information Tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <h5 class="mb-4 text-light fw-semibold border-bottom border-secondary pb-2">Contact Information</h5>
                            <div class="row mb-4">
                                <div class="col-sm-3 text-secondary">Phone</div>
                                <div class="col-sm-9 fw-medium text-light">{{ $student->phone ?? 'Not provided' }}</div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-sm-3 text-secondary">Email</div>
                                <div class="col-sm-9 fw-medium text-light">{{ $student->email ?? 'Not provided' }}</div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-sm-3 text-secondary">Address</div>
                                <div class="col-sm-9 fw-medium text-light">{{ $student->address ?? 'Not provided' }}</div>
                            </div>
                        </div>

                        <!-- Guardians Tab -->
                        <div class="tab-pane fade" id="guardians" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-2">
                                <h5 class="mb-0 text-light fw-semibold">Parents & Guardians</h5>
                                <a href="{{ route('guardians.create', ['student' => $student->uuid]) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-lg"></i> Add guardian
                                </a>
                            </div>

                            @if($student->guardians->count() > 0)
                                <div class="row g-3">
                                    @foreach($student->guardians as $guardian)
                                        <div class="col-md-6">
                                            <div class="guardian-card">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <h6><a href="{{ route('guardians.show', $guardian) }}" class="text-body">{{ $guardian->full_name }}</a></h6>
                                                        <p class="relation">{{ $guardian->pivot->relationship }}</p>
                                                    </div>
                                                    @if($guardian->pivot->is_emergency_contact)
                                                        <span class="badge bg-danger">Emergency contact</span>
                                                    @endif
                                                </div>
                                                <div class="meta"><i class="bi bi-telephone"></i>{{ $guardian->phone }}</div>
                                                @if($guardian->email)
                                                    <div class="meta text-truncate"><i class="bi bi-envelope"></i>{{ $guardian->email }}</div>
                                                @endif
                                                <form action="{{ route('students.guardians.destroy', [$student, $guardian]) }}" method="POST" class="mt-3"
                                                      onsubmit="return confirm('Remove {{ addslashes($guardian->full_name) }} from {{ addslashes($student->full_name) }}? The guardian record itself will be kept.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i> Remove</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-note">
                                    <i class="bi bi-people" aria-hidden="true"></i>
                                    No guardians linked yet. Use <strong>Add guardian</strong> to pick an existing parent or create a new one.
                                </div>
                            @endif
                        </div>

                        <!-- Enrollments Tab -->
                        <div class="tab-pane fade" id="enrollments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-2">
                                <h5 class="mb-0 text-light fw-semibold">Enrollment History</h5>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#enrollModal">
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
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-2">
                                <h5 class="mb-0 text-light fw-semibold">Student Documents</h5>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#documentModal">
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
                @php
                    $enrollErrors = $errors->getBag('enrollment');
                    $enrollClasses = \App\Models\SchoolClass::with(['sections' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();
                @endphp
                <form action="{{ route('students.enrollments.store', $student) }}" method="POST" id="enrollForm" data-has-errors="{{ $enrollErrors->any() ? '1' : '0' }}">
                    @csrf
                    <div class="modal-body">
                        @if($enrollErrors->any())
                            <div class="alert alert-danger">
                                <ul>@foreach($enrollErrors->all() as $msg)<li>{{ $msg }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label" for="enroll-session">Academic session <span class="text-danger">*</span></label>
                            <select name="academic_session_id" id="enroll-session" class="form-select {{ $enrollErrors->has('academic_session_id') ? 'is-invalid' : '' }}" required>
                                <option value="">Select session</option>
                                @foreach(\App\Models\AcademicSession::orderByDesc('start_date')->get() as $session)
                                    <option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="enroll-class">Class <span class="text-danger">*</span></label>
                                <select name="school_class_id" id="enroll-class" class="form-select {{ $enrollErrors->has('school_class_id') ? 'is-invalid' : '' }}" required>
                                    <option value="">Select class</option>
                                    @foreach($enrollClasses as $cls)
                                        <option value="{{ $cls->id }}" @selected(old('school_class_id') == $cls->id)>{{ $cls->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="enroll-section">Section</label>
                                <select name="section_id" id="enroll-section" class="form-select {{ $enrollErrors->has('section_id') ? 'is-invalid' : '' }}" data-selected="{{ old('section_id') }}">
                                    <option value="">Select class first</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="enroll-roll">Roll number</label>
                            <input type="text" name="roll_number" id="enroll-roll" class="form-control" value="{{ old('roll_number') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="enroll-status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="enroll-status" class="form-select" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="promoted" @selected(old('status') === 'promoted')>Promoted</option>
                                <option value="dropped" @selected(old('status') === 'dropped')>Dropped</option>
                            </select>
                        </div>
                    </div>
                    {{-- Sections per class, used to fill the Section dropdown when a class is chosen --}}
                    <script type="application/json" id="enroll-sections-data">
                        {!! json_encode($enrollClasses->mapWithKeys(fn ($c) => [$c->id => $c->sections->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
                    </script>
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

    @push('scripts')
    <script>
        // Open the tab named in ?tab= (e.g. after linking a guardian)
        (function () {
            var tab = new URLSearchParams(window.location.search).get('tab');
            var btn = tab && document.getElementById(tab + '-tab');
            if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
        })();

        // New enrollment: show only the sections of the chosen class
        (function () {
            var classSelect = document.getElementById('enroll-class');
            var sectionSelect = document.getElementById('enroll-section');
            var dataEl = document.getElementById('enroll-sections-data');
            if (!classSelect || !sectionSelect || !dataEl) return;
            var sectionsByClass = JSON.parse(dataEl.textContent || '{}');

            function fillSections(selectedId) {
                var list = sectionsByClass[classSelect.value] || [];
                sectionSelect.innerHTML = '';

                var first = document.createElement('option');
                first.value = '';
                first.textContent = !classSelect.value ? 'Select class first'
                    : (list.length ? 'No section' : 'This class has no sections');
                sectionSelect.appendChild(first);

                list.forEach(function (s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    if (String(s.id) === String(selectedId)) opt.selected = true;
                    sectionSelect.appendChild(opt);
                });
                sectionSelect.disabled = !classSelect.value || list.length === 0;
            }

            classSelect.addEventListener('change', function () { fillSections(''); });
            fillSections(sectionSelect.getAttribute('data-selected'));

            // Re-open the modal with the user's input and errors after a failed submit
            if (document.getElementById('enrollForm').getAttribute('data-has-errors') === '1') {
                var enrollTab = document.getElementById('enrollments-tab');
                if (enrollTab) bootstrap.Tab.getOrCreateInstance(enrollTab).show();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('enrollModal')).show();
            }
        })();
    </script>
    @endpush
</x-app-layout>
