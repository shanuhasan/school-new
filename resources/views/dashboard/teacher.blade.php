<section class="stat-grid" aria-label="Your teaching at a glance">
    <div class="stat">
        <div class="stat-label"><i class="bi bi-door-open" aria-hidden="true"></i>My classes</div>
        <div class="stat-value">{{ $teacher->classes->count() }}</div>
        <div class="stat-meta">assigned to you</div>
    </div>
    <div class="stat">
        <div class="stat-label"><i class="bi bi-book" aria-hidden="true"></i>My subjects</div>
        <div class="stat-value">{{ $teacher->subjects->count() }}</div>
        <div class="stat-meta">{{ $teacher->subjects->pluck('name')->take(2)->implode(', ') ?: '—' }}</div>
    </div>
    <div class="stat">
        <div class="stat-label"><i class="bi bi-journal-text" aria-hidden="true"></i>Homework set</div>
        <div class="stat-value">{{ $homeworkSetCount }}</div>
        <div class="stat-meta"><a href="{{ route('academic.homework.index') }}">View all</a></div>
    </div>
    <div class="stat">
        <div class="stat-label"><i class="bi bi-check2-square" aria-hidden="true"></i>Attendance</div>
        <div class="stat-value" style="font-size:1.2rem"><a href="{{ route('academic.attendance.index') }}" class="text-body">Take &rarr;</a></div>
        <div class="stat-meta">for today</div>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-body p-3 p-lg-4">
                <div class="panel-header mb-3"><h2>My classes</h2></div>
                @if($teacher->classes->isEmpty())
                    <div class="empty-note"><i class="bi bi-door-open" aria-hidden="true"></i>No classes assigned to you yet. Ask an admin to assign classes on your profile.</div>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($teacher->classes as $class)
                            <li class="d-flex align-items-center gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                <span class="fw-semibold me-auto">{{ $class->name }}</span>
                                <a href="{{ route('academic.attendance.index', ['school_class_id' => $class->id]) }}" class="btn btn-sm btn-light" title="Attendance"><i class="bi bi-check2-square"></i><span class="d-none d-sm-inline"> Attendance</span></a>
                                <a href="{{ route('academic.classes.show', $class) }}" class="btn btn-sm btn-light" title="Open class"><i class="bi bi-box-arrow-up-right"></i></a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <hr class="my-4">
                <h3 class="h6 fw-semibold mb-3">Shortcuts</h3>
                <div class="quick-links">
                    <a class="quick-link" href="{{ route('academic.homework.create') }}"><i class="bi bi-journal-plus" aria-hidden="true"></i>Set homework</a>
                    <a class="quick-link" href="{{ route('academic.marks.index') }}"><i class="bi bi-pencil-square" aria-hidden="true"></i>Enter marks</a>
                    <a class="quick-link" href="{{ route('staff.timetables.teacher') }}"><i class="bi bi-calendar-week" aria-hidden="true"></i>My timetable</a>
                    <a class="quick-link" href="{{ route('academic.attendance.index') }}"><i class="bi bi-check2-square" aria-hidden="true"></i>Attendance</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="panel mb-4">
            <div class="panel-body p-3 p-lg-4">
                <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                    <h2>Homework you set</h2>
                    <a href="{{ route('academic.homework.index') }}" class="small fw-medium">All</a>
                </div>
                @include('dashboard.partials.homework-feed', ['items' => $myHomework])
            </div>
        </div>

        <div class="panel">
            <div class="panel-body p-3 p-lg-4">
                <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                    <h2>Upcoming exams</h2>
                    <a href="{{ route('academic.exams.index') }}" class="small fw-medium">All exams</a>
                </div>
                @include('dashboard.partials.exam-feed', ['exams' => $upcomingExams])
            </div>
        </div>
    </div>
</div>
