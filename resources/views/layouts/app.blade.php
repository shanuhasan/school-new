@php
    $user = Auth::user();
    $currentSchool = $user->currentSchool;
    $availableSchools = $user->hasRole('Super Admin') ? \App\Models\School::orderBy('name')->get() : $user->schools;
    $roleName = $user->roles->pluck('name')->first() ?? 'User';
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');

    $section = match (true) {
        request()->routeIs('dashboard') => 'Dashboard',
        request()->routeIs('students.*', 'guardians.*', 'documents.*') => 'Students',
        request()->routeIs('staff.timetables.*', 'academic.timetables.*') => 'Timetable',
        request()->routeIs('staff.*') => 'Staff & Teachers',
        request()->routeIs('academic.exams.*', 'academic.exam-types.*', 'academic.marks.*', 'academic.results.*', 'academic.grades.*') => 'Examinations',
        request()->routeIs('academic.attendance.*') => 'Attendance',
        request()->routeIs('academic.homework.*') => 'Homework',
        request()->routeIs('academic.*') => 'Academic Setup',
        request()->routeIs('reports.*') => 'Reports',
        request()->routeIs('profile.*') => 'Your Account',
        default => '',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  @include('layouts.partials.head', ['title' => $section])
</head>
<body>
  <a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 btn btn-primary" style="z-index:2000" href="#main">Skip to content</a>

  <div class="admin-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>

    @include('layouts.adminhmd-sidebar', ['initials' => $initials, 'roleName' => $roleName, 'currentSchool' => $currentSchool])

    <div class="admin-main">
      @if(session('impersonator_id'))
        <div class="impersonation-bar" role="status">
          <span><i class="bi bi-person-badge" aria-hidden="true"></i>
            You are signed in as <strong>{{ $user->name }}</strong>
            @if(session('impersonator_name')) &mdash; really {{ session('impersonator_name') }} @endif
          </span>
          <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm"><i class="bi bi-box-arrow-left"></i> Stop &amp; return to my account</button>
          </form>
        </div>
      @endif

      <header class="admin-topbar">
        <button class="icon-btn" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle navigation">
          <i class="bi bi-layout-sidebar" aria-hidden="true"></i>
        </button>

        <div class="topbar-title">
          {{ $section ?: config('app.name') }}
          @if($currentSchool)
            <small>{{ $currentSchool->name }}</small>
          @endif
        </div>

        <div class="topbar-actions">
          @if($availableSchools->count() > 1 || $user->hasRole('Super Admin'))
            <div class="dropdown d-none d-sm-block">
              <button class="chip-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-building" aria-hidden="true"></i>
                <span>{{ $currentSchool->name ?? 'Select school' }}</span>
                <i class="bi bi-chevron-expand small text-muted" aria-hidden="true"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Switch school</h6></li>
                @foreach($availableSchools as $avSchool)
                  <li>
                    <form method="POST" action="{{ route('school.switch') }}">
                      @csrf
                      <input type="hidden" name="school_id" value="{{ $avSchool->id }}">
                      <button type="submit" class="dropdown-item {{ $user->current_school_id == $avSchool->id ? 'active' : '' }}">
                        <i class="bi {{ $user->current_school_id == $avSchool->id ? 'bi-check2' : 'bi-building' }}" aria-hidden="true"></i>
                        {{ $avSchool->name }}
                      </button>
                    </form>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          <button class="icon-btn" type="button" data-theme-toggle aria-label="Switch colour theme">
            <i class="bi bi-moon" aria-hidden="true"></i>
          </button>

          <div class="dropdown">
            <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
              <span class="initials">{{ $initials }}</span>
              <span class="user-name d-none d-md-inline">{{ $user->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li class="px-2 pt-2 pb-2 mb-1 border-bottom">
                <div class="fw-semibold text-truncate">{{ $user->name }}</div>
                <div class="small text-muted text-truncate">{{ $user->email }}</div>
              </li>
              @if($availableSchools->count() > 1 || $user->hasRole('Super Admin'))
                <li class="d-sm-none">
                  <h6 class="dropdown-header">Switch school</h6>
                  @foreach($availableSchools as $avSchool)
                    <form method="POST" action="{{ route('school.switch') }}">
                      @csrf
                      <input type="hidden" name="school_id" value="{{ $avSchool->id }}">
                      <button type="submit" class="dropdown-item {{ $user->current_school_id == $avSchool->id ? 'active' : '' }}">
                        <i class="bi bi-building" aria-hidden="true"></i>{{ $avSchool->name }}
                      </button>
                    </form>
                  @endforeach
                  <hr class="dropdown-divider">
                </li>
              @endif
              <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person" aria-hidden="true"></i>Profile &amp; password</a></li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out</button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </header>

      <main class="dashboard-content" id="main">
        <div class="content-wrap">
          @isset($header)
            {{ $header }}
          @endisset

          {{ $slot }}
        </div>
      </main>

      <footer class="admin-footer">
        <div class="footer-inner">
          <span>&copy; {{ date('Y') }} {{ $currentSchool->name ?? config('app.name') }}</span>
          <span>Signed in as {{ $roleName }}</span>
        </div>
      </footer>
    </div>

    {{-- Phone / tablet bottom navigation --}}
    <nav class="mobile-tabbar" aria-label="Quick navigation">
      <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="bi bi-house{{ request()->routeIs('dashboard') ? '-fill' : '' }}" aria-hidden="true"></i>Home
      </a>
      @can('students.create')
      <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*', 'guardians.*') ? 'active' : '' }}">
        <i class="bi bi-people{{ request()->routeIs('students.*', 'guardians.*') ? '-fill' : '' }}" aria-hidden="true"></i>Students
      </a>
      @endcan
      @can('attendance.create')
      <a href="{{ route('academic.attendance.index') }}" class="{{ request()->routeIs('academic.attendance.*') ? 'active' : '' }}">
        <i class="bi bi-check2-square" aria-hidden="true"></i>Attendance
      </a>
      @endcan
      @can('homework.view')
      <a href="{{ route('academic.homework.index') }}" class="{{ request()->routeIs('academic.homework.*') ? 'active' : '' }}">
        <i class="bi bi-journal-bookmark" aria-hidden="true"></i>Homework
      </a>
      @endcan
      @canany(['exams.view', 'marks.enter', 'results.publish'])
      <a href="{{ route('academic.exams.index') }}" class="{{ request()->routeIs('academic.exams.*', 'academic.marks.*', 'academic.results.*') ? 'active' : '' }}">
        <i class="bi bi-journal-text" aria-hidden="true"></i>Exams
      </a>
      @endcanany
      <button type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-label="Open full menu">
        <i class="bi bi-grid" aria-hidden="true"></i>Menu
      </button>
    </nav>
  </div>

  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/app-shell.js') }}?v={{ @filemtime(public_path('assets/js/app-shell.js')) }}"></script>
  @stack('scripts')
</body>
</html>
