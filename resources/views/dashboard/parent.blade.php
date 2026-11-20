@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp
<div class="panel">
    <div class="panel-body p-3 p-lg-4">
        <div class="panel-header mb-3"><h2>My children</h2></div>

        @if($children->isEmpty())
            <div class="empty-note">
                <i class="bi bi-people" aria-hidden="true"></i>
                No children are linked to your account yet. Ask the school office to link your child's record.
            </div>
        @else
            <div class="row g-3">
                @foreach($children as $row)
                    <div class="col-md-6">
                        <div class="guardian-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="initials initials-lg">{{ $initials($row->student->full_name) }}</span>
                                <div class="min-w-0">
                                    <div class="fw-semibold">{{ $row->student->full_name }}</div>
                                    <div class="small text-muted">
                                        @if($row->enrollment)
                                            {{ $row->enrollment->schoolClass->name ?? '' }}{{ $row->enrollment->section ? ' · ' . $row->enrollment->section->name : '' }}
                                        @else
                                            Not enrolled
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-4">
                                <div>
                                    <div class="small text-muted">Attendance</div>
                                    <div class="fw-semibold fs-5">{{ $row->attendanceRate !== null ? $row->attendanceRate . '%' : '—' }}</div>
                                </div>
                                <div>
                                    <div class="small text-muted">Homework due</div>
                                    <div class="fw-semibold fs-5">{{ $row->homeworkDue }}</div>
                                </div>
                            </div>
                            <a href="{{ route('students.show', $row->student) }}" class="btn btn-sm btn-light mt-3"><i class="bi bi-eye"></i> View profile</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
