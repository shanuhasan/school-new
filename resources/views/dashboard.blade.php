@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(Auth::user()->name, ' ');
    $att = $attendance;
@endphp
<x-app-layout>
    <div class="greeting">
        <div class="date">{{ now()->format('l, j F Y') }}</div>
        <h1>{{ $greeting }}, {{ $firstName }}.</h1>
        <p>
            @if($school)
                Here's where {{ $school->name }} stands today.
            @else
                You're not linked to a school yet. Ask an administrator to add you to one.
            @endif
        </p>
    </div>

    @if($stats)
        <section class="stat-grid" aria-label="School at a glance">
            <div class="stat">
                <div class="stat-label"><i class="bi bi-people" aria-hidden="true"></i>Active students</div>
                <div class="stat-value">{{ number_format($stats['students']) }}</div>
                <div class="stat-meta"><a href="{{ route('students.index') }}">View register</a></div>
            </div>
            <div class="stat">
                <div class="stat-label"><i class="bi bi-person-badge" aria-hidden="true"></i>Teachers</div>
                <div class="stat-value">{{ number_format($stats['teachers']) }}</div>
                <div class="stat-meta">{{ number_format($stats['staff']) }} support staff</div>
            </div>
            <div class="stat">
                <div class="stat-label"><i class="bi bi-door-open" aria-hidden="true"></i>Classes</div>
                <div class="stat-value">{{ number_format($stats['classes']) }}</div>
                <div class="stat-meta"><a href="{{ route('academic.classes.index') }}">Manage classes</a></div>
            </div>
            <div class="stat">
                <div class="stat-label"><i class="bi bi-check2-square" aria-hidden="true"></i>Attendance today</div>
                <div class="stat-value">{{ $att['rate'] !== null ? $att['rate'] . '%' : '—' }}</div>
                <div class="stat-meta">
                    @if($att['marked'] > 0)
                        {{ number_format($att['marked']) }} of {{ number_format($stats['students']) }} marked
                    @else
                        Not taken yet
                    @endif
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="panel h-100">
                    <div class="panel-body p-3 p-lg-4">
                        <div class="panel-header d-flex justify-content-between align-items-center mb-1">
                            <h2>Today's attendance</h2>
                            <a href="{{ route('academic.attendance.mark') }}" class="btn btn-sm btn-light">Take attendance</a>
                        </div>

                        @if($att['marked'] > 0)
                            @php $pct = fn ($n) => round($n / $att['marked'] * 100, 1); @endphp
                            <div class="attendance-bar" role="img" aria-label="{{ $att['present'] }} present, {{ $att['late'] }} late, {{ $att['half_day'] }} half day, {{ $att['absent'] }} absent">
                                <span style="width: {{ $pct($att['present']) }}%; background: var(--success)"></span>
                                <span style="width: {{ $pct($att['late']) }}%; background: var(--accent)"></span>
                                <span style="width: {{ $pct($att['half_day']) }}%; background: var(--info)"></span>
                                <span style="width: {{ $pct($att['absent']) }}%; background: var(--danger)"></span>
                            </div>
                            <div class="legend">
                                <span><i style="background: var(--success)"></i>Present <strong class="text-body">{{ $att['present'] }}</strong></span>
                                <span><i style="background: var(--accent)"></i>Late <strong class="text-body">{{ $att['late'] }}</strong></span>
                                <span><i style="background: var(--info)"></i>Half day <strong class="text-body">{{ $att['half_day'] }}</strong></span>
                                <span><i style="background: var(--danger)"></i>Absent <strong class="text-body">{{ $att['absent'] }}</strong></span>
                            </div>
                        @else
                            <div class="empty-note">
                                <i class="bi bi-clipboard" aria-hidden="true"></i>
                                No attendance has been recorded for today yet.
                            </div>
                        @endif

                        <hr class="my-4">

                        <h3 class="h6 fw-semibold mb-3">Shortcuts</h3>
                        <div class="quick-links">
                            <a class="quick-link" href="{{ route('students.create') }}"><i class="bi bi-person-plus" aria-hidden="true"></i>Admit a student</a>
                            <a class="quick-link" href="{{ route('academic.homework.create') }}"><i class="bi bi-journal-plus" aria-hidden="true"></i>Set homework</a>
                            <a class="quick-link" href="{{ route('academic.marks.index') }}"><i class="bi bi-pencil-square" aria-hidden="true"></i>Enter marks</a>
                            <a class="quick-link" href="{{ route('reports.attendance.monthly') }}"><i class="bi bi-calendar3" aria-hidden="true"></i>Monthly report</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="panel mb-4">
                    <div class="panel-body p-3 p-lg-4">
                        <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                            <h2>Upcoming exams</h2>
                            <a href="{{ route('academic.exams.index') }}" class="small fw-medium">All exams</a>
                        </div>
                        @if($upcomingExams->isNotEmpty())
                            <ul class="feed">
                                @foreach($upcomingExams as $exam)
                                    <li>
                                        <div class="feed-date">
                                            <b>{{ $exam->start_date?->format('j') }}</b>
                                            <small>{{ $exam->start_date?->format('M') }}</small>
                                        </div>
                                        <div class="feed-body">
                                            <strong><a href="{{ route('academic.exams.show', $exam) }}" class="text-body">{{ $exam->name }}</a></strong>
                                            <span>
                                                {{ $exam->examType->name ?? 'Exam' }}
                                                @if($exam->start_date && $exam->end_date && ! $exam->start_date->isSameDay($exam->end_date))
                                                    · until {{ $exam->end_date->format('j M') }}
                                                @endif
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="empty-note"><i class="bi bi-calendar-x" aria-hidden="true"></i>No exams scheduled.</div>
                        @endif
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-body p-3 p-lg-4">
                        <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                            <h2>Homework due</h2>
                            <a href="{{ route('academic.homework.index') }}" class="small fw-medium">All homework</a>
                        </div>
                        @if($homeworkDue->isNotEmpty())
                            <ul class="feed">
                                @foreach($homeworkDue as $hw)
                                    <li>
                                        <div class="feed-date">
                                            <b>{{ $hw->due_date?->format('j') }}</b>
                                            <small>{{ $hw->due_date?->format('M') }}</small>
                                        </div>
                                        <div class="feed-body">
                                            <strong><a href="{{ route('academic.homework.show', $hw) }}" class="text-body">{{ $hw->title }}</a></strong>
                                            <span>
                                                {{ $hw->subject->name ?? '' }}
                                                @if($hw->schoolClass) · {{ $hw->schoolClass->name }}{{ $hw->section ? ' ' . $hw->section->name : '' }} @endif
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="empty-note"><i class="bi bi-journal-check" aria-hidden="true"></i>Nothing due. All caught up.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
