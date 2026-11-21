@php
    $initials = mb_substr($guardian->first_name, 0, 1) . mb_substr($guardian->last_name, 0, 1);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Guardian</span>
                    <h1 class="h3 mb-1">{{ $guardian->full_name }}</h1>
                </div>
                <div>
                    <a href="{{ route('guardians.edit', $guardian) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="{{ route('guardians.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to guardians</a>
                </div>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="initials" style="width:52px;height:52px;font-size:1rem">{{ $initials }}</span>
                        <div>
                            <div class="fw-semibold fs-5">{{ $guardian->full_name }}</div>
                            @if($guardian->occupation)<div class="text-muted small">{{ $guardian->occupation }}</div>@endif
                        </div>
                    </div>

                    <dl class="mb-0">
                        <dt class="small text-muted fw-medium">Phone</dt>
                        <dd class="mb-3"><a href="tel:{{ preg_replace('/[^\d+]/', '', $guardian->phone) }}" class="text-body">{{ $guardian->phone }}</a></dd>

                        <dt class="small text-muted fw-medium">Email</dt>
                        <dd class="mb-3">
                            @if($guardian->email)
                                <a href="mailto:{{ $guardian->email }}" class="text-body">{{ $guardian->email }}</a>
                            @else
                                <span class="text-muted">Not provided</span>
                            @endif
                        </dd>

                        <dt class="small text-muted fw-medium">Address</dt>
                        <dd class="mb-0">{!! $guardian->address ? nl2br(e($guardian->address)) : '<span class="text-muted">Not provided</span>' !!}</dd>
                    </dl>

                    <hr>
                    <h6 class="fw-semibold mb-2"><i class="bi bi-box-arrow-in-right me-1"></i> Login access</h6>
                    @if($guardian->user_id)
                        <div class="small text-muted mb-1">Signs in with</div>
                        <div class="fw-medium text-truncate">{{ optional($guardian->user)->email ?? $guardian->email }}</div>
                        <span class="badge bg-success mt-2">Account active</span>
                    @elseif($guardian->email)
                        <p class="small text-muted mb-2">No login account yet.</p>
                        <form method="POST" action="{{ route('guardians.login-account', $guardian) }}"
                              onsubmit="return confirm('Create a login account for {{ addslashes($guardian->full_name) }} ({{ $guardian->email }})?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-person-plus"></i> Create login account</button>
                        </form>
                    @else
                        <p class="small text-muted mb-0">Add an email address (Edit) to enable a login account.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>Children</h2>
                        <span class="text-muted small">{{ $guardian->students->count() }} linked</span>
                    </div>

                    @if($guardian->students->isEmpty())
                        <div class="empty-note">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            Not linked to any student yet. Open a student's profile → Guardians → Add guardian to link them.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Adm. No</th>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Relationship</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($guardian->students as $student)
                                        @php $enrollment = $student->enrollments->firstWhere('status', 'active'); @endphp
                                        <tr>
                                            <td><span class="badge bg-secondary">{{ $student->admission_number }}</span></td>
                                            <td>
                                                <a href="{{ route('students.show', ['student' => $student, 'tab' => 'guardians']) }}" class="fw-semibold text-body">{{ $student->full_name }}</a>
                                                @if($student->pivot->is_emergency_contact)
                                                    <span class="badge bg-danger ms-1">Emergency contact</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($enrollment)
                                                    {{ $enrollment->schoolClass->name ?? '' }}{{ $enrollment->section ? ' - ' . $enrollment->section->name : '' }}
                                                @else
                                                    <span class="text-muted">Not enrolled</span>
                                                @endif
                                            </td>
                                            <td>{{ $student->pivot->relationship }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-info" title="View student" aria-label="View student"><i class="bi bi-eye"></i></a>
                                                <form action="{{ route('students.guardians.destroy', [$student, $guardian]) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Unlink {{ addslashes($guardian->full_name) }} from {{ addslashes($student->full_name) }}? Both records are kept.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Unlink" aria-label="Unlink"><i class="bi bi-link-45deg"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
