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
