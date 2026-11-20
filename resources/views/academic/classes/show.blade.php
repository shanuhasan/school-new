@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $unsectioned = $sectionCounts[0] ?? 0;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Class{{ $class->level !== null && $class->level !== '' ? ' · Level ' . $class->level : '' }}</span>
                    <h1 class="h3 mb-1">{{ $class->name }}</h1>
                    <p>
                        @if($session)
                            Showing students for {{ $session->name }}{{ $session->is_current ? '' : ' (latest session)' }}.
                        @else
                            No academic session yet. Create one to enrol students.
                        @endif
                    </p>
                </div>
                <div>
                    <a href="{{ route('academic.classes.edit', $class) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="{{ route('academic.classes.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to classes</a>
                </div>
            </div>
        </div>
    </x-slot>

    <section class="stat-grid" aria-label="Class summary">
        <div class="stat">
            <div class="stat-label"><i class="bi bi-people" aria-hidden="true"></i>Students</div>
            <div class="stat-value">{{ $enrollments->count() }}</div>
            <div class="stat-meta">active this session</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-grid-3x2-gap" aria-hidden="true"></i>Sections</div>
            <div class="stat-value">{{ $class->sections->count() }}</div>
            <div class="stat-meta"><a href="{{ route('academic.sections.create') }}">Add a section</a></div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-book" aria-hidden="true"></i>Subjects</div>
            <div class="stat-value">{{ $class->subjects->count() }}</div>
            <div class="stat-meta">{{ $class->subjects->where('pivot.type', 'optional')->count() }} optional</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-person-badge" aria-hidden="true"></i>Teachers</div>
            <div class="stat-value">{{ $teachers->count() }}</div>
            <div class="stat-meta">assigned to this class</div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-5">
            {{-- Sections --}}
            <div class="panel mb-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>Sections</h2>
                    </div>
                    @if($class->sections->isEmpty())
                        <div class="empty-note">
                            <i class="bi bi-grid-3x2-gap" aria-hidden="true"></i>
                            No sections yet. <a href="{{ route('academic.sections.create') }}">Add one</a> to take attendance and build timetables.
                        </div>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($class->sections as $section)
                                <li class="d-flex align-items-center gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <div class="me-auto">
                                        <div class="fw-semibold">Section {{ $section->name }}</div>
                                        <div class="small text-muted">{{ $sectionCounts[$section->id] ?? 0 }} {{ \Illuminate\Support\Str::plural('student', $sectionCounts[$section->id] ?? 0) }}</div>
                                    </div>
                                    <a href="{{ route('academic.attendance.mark', ['school_class_id' => $class->id, 'section_id' => $section->id, 'date' => now()->toDateString()]) }}"
                                       class="btn btn-sm btn-light" title="Take today's attendance"><i class="bi bi-check2-square"></i><span class="d-none d-sm-inline"> Attendance</span></a>
                                    <a href="{{ route('academic.timetables.manage', ['school_class_id' => $class->id, 'section_id' => $section->id]) }}"
                                       class="btn btn-sm btn-light" title="Timetable"><i class="bi bi-calendar-week"></i><span class="d-none d-sm-inline"> Timetable</span></a>
                                </li>
                            @endforeach
                        </ul>
                        @if($unsectioned)
                            <p class="small text-muted mt-3 mb-0">{{ $unsectioned }} {{ \Illuminate\Support\Str::plural('student', $unsectioned) }} enrolled without a section.</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Subjects --}}
            <div class="panel mb-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>Subjects</h2>
                        <a href="{{ route('academic.classes.edit', $class) }}" class="small fw-medium">Change</a>
                    </div>
                    @if($class->subjects->isEmpty())
                        <div class="empty-note"><i class="bi bi-book" aria-hidden="true"></i>No subjects assigned to this class.</div>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($class->subjects as $subject)
                                <a href="{{ route('academic.subjects.show', $subject) }}" class="badge {{ $subject->pivot->type === 'optional' ? 'bg-secondary' : 'bg-primary' }} text-decoration-none fw-semibold" style="font-size:.8rem;padding:.45em .7em">
                                    {{ $subject->name }}@if($subject->code) <span class="opacity-75 fw-normal">· {{ $subject->code }}</span>@endif
                                    @if($subject->pivot->type === 'optional') <span class="fw-normal">(optional)</span>@endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Teachers --}}
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Teachers</h2></div>
                    @if($teachers->isEmpty())
                        <div class="empty-note"><i class="bi bi-person-badge" aria-hidden="true"></i>No teachers assigned. Assign classes on a teacher's profile.</div>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($teachers as $teacher)
                                <li class="d-flex align-items-center gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <span class="initials">{{ $initials($teacher->user?->name) }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('staff.teachers.show', $teacher) }}" class="fw-semibold text-body">{{ $teacher->user?->name }}</a>
                                        <div class="small text-muted text-truncate">{{ $teacher->subjects->pluck('name')->implode(', ') ?: 'No subjects set' }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Students --}}
        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>Students</h2>
                        @if($enrollments->isNotEmpty())
                            <input type="search" class="form-control form-control-sm" style="max-width:220px" placeholder="Filter students…" data-table-search="class-students" aria-label="Filter students">
                        @endif
                    </div>

                    @if($enrollments->isEmpty())
                        <div class="empty-note">
                            <i class="bi bi-people" aria-hidden="true"></i>
                            No students enrolled{{ $session ? ' in ' . $session->name : '' }}.
                            Enrol them from a student's profile, or include a <code>class</code> column when you <a href="{{ route('students.import.create') }}">import students</a>.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="class-students">
                                <thead>
                                    <tr>
                                        <th>Roll</th>
                                        <th>Student</th>
                                        <th>Section</th>
                                        <th>Adm. No</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enrollments as $enrollment)
                                        @php $s = $enrollment->student; @endphp
                                        <tr>
                                            <td class="text-muted">{{ $enrollment->roll_number ?: '—' }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="initials">{{ $initials($s->full_name) }}</span>
                                                    <a href="{{ route('students.show', $s) }}" class="fw-semibold text-body">{{ $s->full_name }}</a>
                                                </div>
                                            </td>
                                            <td>{{ $enrollment->section->name ?? '—' }}</td>
                                            <td><span class="badge bg-secondary">{{ $s->admission_number }}</span></td>
                                            <td class="text-end">
                                                <a href="{{ route('students.show', $s) }}" class="btn btn-sm btn-info" title="View student" aria-label="View student"><i class="bi bi-eye"></i></a>
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
