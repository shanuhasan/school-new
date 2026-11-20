@php $att = $attendance; @endphp
<section class="stat-grid" aria-label="Your overview">
    <div class="stat">
        <div class="stat-label"><i class="bi bi-mortarboard" aria-hidden="true"></i>My class</div>
        <div class="stat-value" style="font-size:1.4rem">
            @if($enrollment){{ $enrollment->schoolClass->name ?? '—' }}@else — @endif
        </div>
        <div class="stat-meta">
            @if($enrollment && $enrollment->section)Section {{ $enrollment->section->name }}@endif
            @if($enrollment && $enrollment->roll_number) · Roll {{ $enrollment->roll_number }}@endif
        </div>
    </div>
    <div class="stat">
        <div class="stat-label"><i class="bi bi-check2-square" aria-hidden="true"></i>My attendance</div>
        <div class="stat-value">{{ $att && $att['rate'] !== null ? $att['rate'] . '%' : '—' }}</div>
        <div class="stat-meta">{{ $att ? $att['marked'] . ' days recorded' : 'this session' }}</div>
    </div>
    <div class="stat">
        <div class="stat-label"><i class="bi bi-journal-text" aria-hidden="true"></i>Homework due</div>
        <div class="stat-value">{{ $homeworkDue->count() }}</div>
        <div class="stat-meta"><a href="{{ route('academic.homework.index') }}">Open homework</a></div>
    </div>
    <div class="stat">
        <div class="stat-label"><i class="bi bi-calendar-event" aria-hidden="true"></i>Next exam</div>
        <div class="stat-value" style="font-size:1.2rem">
            @if($upcomingExams->isNotEmpty()){{ $upcomingExams->first()->start_date?->format('j M') }}@else — @endif
        </div>
        <div class="stat-meta">{{ $upcomingExams->first()->name ?? 'none scheduled' }}</div>
    </div>
</section>

@if($att && $att['marked'] > 0)
    <div class="panel mb-4">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header mb-3"><h2>My attendance this session</h2></div>
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
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-body p-3 p-lg-4">
                <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                    <h2>Homework due</h2>
                    <a href="{{ route('academic.homework.index') }}" class="small fw-medium">All homework</a>
                </div>
                @include('dashboard.partials.homework-feed', ['items' => $homeworkDue])
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-body p-3 p-lg-4">
                <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                    <h2>Upcoming exams</h2>
                </div>
                @include('dashboard.partials.exam-feed', ['exams' => $upcomingExams])
            </div>
        </div>
    </div>
</div>
