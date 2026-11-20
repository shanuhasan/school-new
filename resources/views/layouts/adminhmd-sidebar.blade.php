@php
    $setupOpen = request()->routeIs('academic.sessions.*', 'academic.classes.*', 'academic.sections.*', 'academic.subjects.*');
    $studentsOpen = request()->routeIs('students.*', 'guardians.*');
    $staffOpen = request()->routeIs('staff.teachers.*', 'staff.staffs.*', 'staff.departments.*', 'staff.designations.*');
    $examsOpen = request()->routeIs('academic.exams.*', 'academic.exam-types.*', 'academic.grades.*', 'academic.marks.*', 'academic.results.*');
    $reportsOpen = request()->routeIs('reports.*');
    $timetableOpen = request()->routeIs('academic.timetables.*', 'staff.timetables.*');
    $schoolLabel = $currentSchool->name ?? config('app.name');
@endphp
<aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
  <div class="sidebar-header">
    <a class="brand-mark" href="{{ route('dashboard') }}">
      <span class="brand-icon" aria-hidden="true">{{ mb_strtoupper(mb_substr($schoolLabel, 0, 1)) }}</span>
      <span class="brand-copy">
        <span class="brand-title">{{ $schoolLabel }}</span>
        <span class="brand-subtitle">School office</span>
      </span>
    </a>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-group-label">Overview</div>
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif title="Dashboard">
      <span class="nav-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
      <span class="nav-text">Dashboard</span>
    </a>

    <div class="nav-group-label">People</div>
    <div class="nav-item">
      <a class="nav-link {{ $studentsOpen ? '' : 'collapsed' }}" href="#studentsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $studentsOpen ? 'true' : 'false' }}" aria-controls="studentsSubmenu" title="Students">
        <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        <span class="nav-text">Students</span>
      </a>
      <div class="collapse {{ $studentsOpen ? 'show' : '' }}" id="studentsSubmenu">
        <ul class="sub-nav">
          <li><a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}">All students</a></li>
          <li><a class="nav-link {{ request()->routeIs('guardians.*') ? 'active' : '' }}" href="{{ route('guardians.index') }}">Guardians</a></li>
        </ul>
      </div>
    </div>

    <div class="nav-item">
      <a class="nav-link {{ $staffOpen ? '' : 'collapsed' }}" href="#staffSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $staffOpen ? 'true' : 'false' }}" aria-controls="staffSubmenu" title="Staff & Teachers">
        <span class="nav-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
        <span class="nav-text">Staff &amp; Teachers</span>
      </a>
      <div class="collapse {{ $staffOpen ? 'show' : '' }}" id="staffSubmenu">
        <ul class="sub-nav">
          <li><a class="nav-link {{ request()->routeIs('staff.teachers.*') ? 'active' : '' }}" href="{{ route('staff.teachers.index') }}">Teachers</a></li>
          <li><a class="nav-link {{ request()->routeIs('staff.staffs.*') ? 'active' : '' }}" href="{{ route('staff.staffs.index') }}">General staff</a></li>
          <li><a class="nav-link {{ request()->routeIs('staff.departments.*') ? 'active' : '' }}" href="{{ route('staff.departments.index') }}">Departments</a></li>
          <li><a class="nav-link {{ request()->routeIs('staff.designations.*') ? 'active' : '' }}" href="{{ route('staff.designations.index') }}">Designations</a></li>
        </ul>
      </div>
    </div>

    <div class="nav-group-label">Teaching</div>
    <a class="nav-link {{ request()->routeIs('academic.attendance.*') ? 'active' : '' }}" href="{{ route('academic.attendance.index') }}" title="Attendance">
      <span class="nav-icon"><i class="bi bi-check2-square" aria-hidden="true"></i></span>
      <span class="nav-text">Attendance</span>
    </a>
    <a class="nav-link {{ request()->routeIs('academic.homework.*') ? 'active' : '' }}" href="{{ route('academic.homework.index') }}" title="Homework">
      <span class="nav-icon"><i class="bi bi-journal-bookmark" aria-hidden="true"></i></span>
      <span class="nav-text">Homework</span>
    </a>

    <div class="nav-item">
      <a class="nav-link {{ $timetableOpen ? '' : 'collapsed' }}" href="#timetableSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $timetableOpen ? 'true' : 'false' }}" aria-controls="timetableSubmenu" title="Timetable">
        <span class="nav-icon"><i class="bi bi-calendar-week" aria-hidden="true"></i></span>
        <span class="nav-text">Timetable</span>
      </a>
      <div class="collapse {{ $timetableOpen ? 'show' : '' }}" id="timetableSubmenu">
        <ul class="sub-nav">
          <li><a class="nav-link {{ request()->routeIs('academic.timetables.*') ? 'active' : '' }}" href="{{ route('academic.timetables.index') }}">Class timetables</a></li>
          @role('Teacher')
          <li><a class="nav-link {{ request()->routeIs('staff.timetables.teacher') ? 'active' : '' }}" href="{{ route('staff.timetables.teacher') }}">My timetable</a></li>
          @endrole
        </ul>
      </div>
    </div>

    <div class="nav-item">
      <a class="nav-link {{ $examsOpen ? '' : 'collapsed' }}" href="#examsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $examsOpen ? 'true' : 'false' }}" aria-controls="examsSubmenu" title="Examinations">
        <span class="nav-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
        <span class="nav-text">Examinations</span>
      </a>
      <div class="collapse {{ $examsOpen ? 'show' : '' }}" id="examsSubmenu">
        <ul class="sub-nav">
          <li><a class="nav-link {{ request()->routeIs('academic.exams.*') ? 'active' : '' }}" href="{{ route('academic.exams.index') }}">Exams</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.marks.*') ? 'active' : '' }}" href="{{ route('academic.marks.index') }}">Marks entry</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.results.*') ? 'active' : '' }}" href="{{ route('academic.results.index') }}">Results</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.grades.*') ? 'active' : '' }}" href="{{ route('academic.grades.index') }}">Grading scale</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.exam-types.*') ? 'active' : '' }}" href="{{ route('academic.exam-types.index') }}">Exam types</a></li>
        </ul>
      </div>
    </div>

    <div class="nav-group-label">Administration</div>
    <div class="nav-item">
      <a class="nav-link {{ $setupOpen ? '' : 'collapsed' }}" href="#academicSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $setupOpen ? 'true' : 'false' }}" aria-controls="academicSubmenu" title="Academic setup">
        <span class="nav-icon"><i class="bi bi-sliders2" aria-hidden="true"></i></span>
        <span class="nav-text">Academic setup</span>
      </a>
      <div class="collapse {{ $setupOpen ? 'show' : '' }}" id="academicSubmenu">
        <ul class="sub-nav">
          <li><a class="nav-link {{ request()->routeIs('academic.sessions.*') ? 'active' : '' }}" href="{{ route('academic.sessions.index') }}">Sessions</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.classes.*') ? 'active' : '' }}" href="{{ route('academic.classes.index') }}">Classes</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.sections.*') ? 'active' : '' }}" href="{{ route('academic.sections.index') }}">Sections</a></li>
          <li><a class="nav-link {{ request()->routeIs('academic.subjects.*') ? 'active' : '' }}" href="{{ route('academic.subjects.index') }}">Subjects</a></li>
        </ul>
      </div>
    </div>

    <div class="nav-item">
      <a class="nav-link {{ $reportsOpen ? '' : 'collapsed' }}" href="#reportsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}" aria-controls="reportsSubmenu" title="Reports">
        <span class="nav-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></span>
        <span class="nav-text">Reports</span>
      </a>
      <div class="collapse {{ $reportsOpen ? 'show' : '' }}" id="reportsSubmenu">
        <ul class="sub-nav">
          <li><a class="nav-link {{ request()->routeIs('reports.attendance.daily') ? 'active' : '' }}" href="{{ route('reports.attendance.daily') }}">Daily attendance</a></li>
          <li><a class="nav-link {{ request()->routeIs('reports.attendance.monthly') ? 'active' : '' }}" href="{{ route('reports.attendance.monthly') }}">Monthly attendance</a></li>
          <li><a class="nav-link {{ request()->routeIs('reports.attendance.student') ? 'active' : '' }}" href="{{ route('reports.attendance.student') }}">Student attendance</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="sidebar-user">
    <span class="initials">{{ $initials }}</span>
    <span class="user-meta">
      <strong>{{ Auth::user()->name }}</strong>
      <small>{{ $roleName }}</small>
    </span>
  </div>
</aside>
