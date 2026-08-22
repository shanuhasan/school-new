<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Laravel') }}</title>

  <!-- Bootstrap 5 CSS from AdminHMD Theme -->
  <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>
<body>
  <div class="admin-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <!-- Sidebar -->
    @include('layouts.adminhmd-sidebar')

    <div class="admin-main">
      <!-- Top Navbar -->
      <nav class="navbar admin-navbar navbar-expand bg-white">
        <div class="container-fluid px-3 px-lg-4">
          <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle sidebar">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <div class="d-flex align-items-center ms-auto gap-3">
            
            <!-- School Switcher Dropdown -->
            @php
                $user = Auth::user();
                $availableSchools = $user->hasRole('Super Admin') ? \App\Models\School::all() : $user->schools;
            @endphp
            @if($availableSchools->count() > 1 || $user->hasRole('Super Admin'))
            <div class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-building"></i>
                <span class="d-none d-md-block">{{ optional($user->currentSchool)->name ?? 'Select School' }}</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li class="dropdown-header">Switch School</li>
                @foreach($availableSchools as $avSchool)
                <li>
                  <form method="POST" action="{{ route('school.switch') }}">
                    @csrf
                    <input type="hidden" name="school_id" value="{{ $avSchool->id }}">
                    <button type="submit" class="dropdown-item {{ $user->current_school_id == $avSchool->id ? 'active' : '' }}">
                        {{ $avSchool->name }}
                    </button>
                  </form>
                </li>
                @endforeach
              </ul>
            </div>
            @endif

            <div class="nav-item dropdown">
              <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                  {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <span class="d-none d-md-block">{{ Auth::user()->name }}</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Log Out</button>
                  </form>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </nav>

      <!-- Main Content -->
      <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">
          @isset($header)
              {{ $header }}
          @endisset

          {{ $slot }}
        </div>
      </main>

      <footer class="admin-footer">
        <div class="container-fluid px-3 px-lg-4 d-flex justify-content-between align-items-center text-muted small">
          <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
          <span>School Management System</span>
        </div>
      </footer>
    </div>
  </div>

  <!-- AdminHMD JS -->
  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
