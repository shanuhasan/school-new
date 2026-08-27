<aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
  <div class="sidebar-header">
    <a class="brand-mark" href="{{ route('dashboard') }}" aria-label="{{ config('app.name') }}">
      <span class="brand-icon"><i class="bi bi-mortarboard-fill text-primary" aria-hidden="true"></i></span>
      <span class="brand-copy">
        <span class="brand-title">{{ config('app.name') }}</span>
        <span class="brand-subtitle">School System</span>
      </span>
    </a>
  </div>

  <nav class="sidebar-nav">
    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-current="page">
      <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
      <span class="nav-text">Dashboard</span>
    </a>
    
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('academic.*') ? '' : 'collapsed' }}" href="#academicSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('academic.*') ? 'true' : 'false' }}" aria-controls="academicSubmenu">
        <span class="nav-icon"><i class="bi bi-book" aria-hidden="true"></i></span>
        <span class="nav-text">Academic</span>
      </a>
      <div class="collapse {{ request()->routeIs('academic.*') ? 'show' : '' }}" id="academicSubmenu">
        <ul class="nav flex-column sub-nav">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('academic.sessions.*') ? 'active' : '' }}" href="{{ route('academic.sessions.index') }}">Sessions</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('academic.classes.*') ? 'active' : '' }}" href="{{ route('academic.classes.index') }}">Classes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('academic.sections.*') ? 'active' : '' }}" href="{{ route('academic.sections.index') }}">Sections</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('academic.subjects.*') ? 'active' : '' }}" href="{{ route('academic.subjects.index') }}">Subjects</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('academic.attendance.*') ? 'active' : '' }}" href="{{ route('academic.attendance.index') }}">Attendance</a>
          </li>
        </ul>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('students.*') || request()->routeIs('guardians.*') ? '' : 'collapsed' }}" href="#studentsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('students.*') || request()->routeIs('guardians.*') ? 'true' : 'false' }}" aria-controls="studentsSubmenu">
        <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
        <span class="nav-text">Students</span>
      </a>
      <div class="collapse {{ request()->routeIs('students.*') || request()->routeIs('guardians.*') ? 'show' : '' }}" id="studentsSubmenu">
        <ul class="nav flex-column sub-nav">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}">All Students</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('guardians.*') ? 'active' : '' }}" href="{{ route('guardians.index') }}">Guardians</a>
          </li>
        </ul>
      </div>
    </div>
    
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('staff.*') ? '' : 'collapsed' }}" href="#staffSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('staff.*') ? 'true' : 'false' }}" aria-controls="staffSubmenu">
        <span class="nav-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
        <span class="nav-text">Staff & Teachers</span>
      </a>
      <div class="collapse {{ request()->routeIs('staff.*') ? 'show' : '' }}" id="staffSubmenu">
        <ul class="nav flex-column sub-nav">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.teachers.*') ? 'active' : '' }}" href="{{ route('staff.teachers.index') }}">Teachers</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.staffs.*') ? 'active' : '' }}" href="{{ route('staff.staffs.index') }}">General Staff</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.departments.*') ? 'active' : '' }}" href="{{ route('staff.departments.index') }}">Departments</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.designations.*') ? 'active' : '' }}" href="{{ route('staff.designations.index') }}">Designations</a>
          </li>
        </ul>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('reports.*') ? '' : 'collapsed' }}" href="#reportsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}" aria-controls="reportsSubmenu">
        <span class="nav-icon"><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
        <span class="nav-text">Reports</span>
      </a>
      <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportsSubmenu">
        <ul class="nav flex-column sub-nav">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.attendance.daily') ? 'active' : '' }}" href="{{ route('reports.attendance.daily') }}">Daily Attendance</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.attendance.monthly') ? 'active' : '' }}" href="{{ route('reports.attendance.monthly') }}">Monthly Attendance</a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('reports.attendance.student') ? 'active' : '' }}" href="{{ route('reports.attendance.student') }}">Student Attendance</a>
          </li>
        </ul>
      </div>
    </div>
    <div class="nav-item">
      <a class="nav-link {{ request()->routeIs('academic.timetables.*') || request()->routeIs('staff.timetables.*') ? '' : 'collapsed' }}" href="#timetableSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('academic.timetables.*') || request()->routeIs('staff.timetables.*') ? 'true' : 'false' }}" aria-controls="timetableSubmenu">
        <span class="nav-icon"><i class="bi bi-calendar-week" aria-hidden="true"></i></span>
        <span class="nav-text">Timetable</span>
      </a>
      <div class="collapse {{ request()->routeIs('academic.timetables.*') || request()->routeIs('staff.timetables.*') ? 'show' : '' }}" id="timetableSubmenu">
        <ul class="nav flex-column sub-nav">
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('academic.timetables.index') ? 'active' : '' }}" href="{{ route('academic.timetables.index') }}">Manage Timetable</a>
          </li>
          @role('Teacher')
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('staff.timetables.teacher') ? 'active' : '' }}" href="{{ route('staff.timetables.teacher') }}">My Timetable</a>
          </li>
          @endrole
        </ul>
      </div>
    </div>
  </nav>

  <div class="sidebar-user">
    <div class="avatar avatar-md bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mb-2 mx-auto" style="width:48px; height:48px; font-size: 1.5rem;">
      {{ substr(optional(Auth::user()->currentSchool)->name ?? 'SA', 0, 2) }}
    </div>
    <strong>{{ optional(Auth::user()->currentSchool)->name ?? 'Super Admin' }}</strong>
    <small>{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</small>
  </div>
</aside>
