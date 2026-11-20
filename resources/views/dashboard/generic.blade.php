@php
    // Menu-style shortcuts, shown only for what this user is actually allowed to open.
    $shortcuts = collect([
        ['can' => 'students.create', 'route' => 'students.index', 'icon' => 'bi-people', 'label' => 'Students'],
        ['can' => 'staff.manage', 'route' => 'staff.teachers.index', 'icon' => 'bi-person-badge', 'label' => 'Teachers & staff'],
        ['can' => 'attendance.create', 'route' => 'academic.attendance.index', 'icon' => 'bi-check2-square', 'label' => 'Attendance'],
        ['can' => 'homework.view', 'route' => 'academic.homework.index', 'icon' => 'bi-journal-text', 'label' => 'Homework'],
        ['can' => 'timetable.view', 'route' => 'academic.timetables.index', 'icon' => 'bi-calendar-week', 'label' => 'Timetable'],
        ['can' => 'attendance.view_reports', 'route' => 'reports.attendance.monthly', 'icon' => 'bi-bar-chart-line', 'label' => 'Reports'],
    ])->filter(fn ($s) => auth()->user()->can($s['can']));
@endphp
<div class="panel">
    <div class="panel-body p-3 p-lg-4">
        @if($shortcuts->isEmpty())
            <div class="empty-note">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                Your role doesn't have any modules enabled yet.
                A Super Admin can grant your role access under <strong>Administration → Roles &amp; Permissions</strong>.
            </div>
        @else
            <div class="panel-header mb-3"><h2>What you can do</h2></div>
            <div class="quick-links">
                @foreach($shortcuts as $s)
                    <a class="quick-link" href="{{ route($s['route']) }}"><i class="bi {{ $s['icon'] }}" aria-hidden="true"></i>{{ $s['label'] }}</a>
                @endforeach
            </div>
        @endif
    </div>
</div>
