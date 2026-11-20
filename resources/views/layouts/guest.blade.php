<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  @include('layouts.partials.head', ['title' => $title ?? null])
</head>
<body>
  <div class="auth-page">
    <aside class="auth-aside" aria-hidden="true">
      <a class="brand-mark" href="{{ url('/') }}" tabindex="-1">
        <span class="brand-icon">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 1)) }}</span>
        <span class="brand-copy">
          <span class="brand-title">{{ config('app.name') }}</span>
          <span class="brand-subtitle">School office</span>
        </span>
      </a>

      <div class="auth-quote">
        <h2>Registers, timetables and report cards, kept in one place.</h2>
        <p>Sign in to take attendance, set homework, enter marks and see how every class is doing today.</p>
      </div>

      <div class="auth-modules">
        <span>Admissions</span><span>Attendance</span><span>Timetable</span><span>Homework</span><span>Examinations</span><span>Reports</span>
      </div>
    </aside>

    <main class="auth-main">
      <div class="auth-card">
        <div class="auth-mobile-brand">
          <a class="brand-mark" href="{{ url('/') }}">
            <span class="brand-icon">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 1)) }}</span>
            <span class="brand-copy">
              <span class="brand-title">{{ config('app.name') }}</span>
            </span>
          </a>
        </div>

        {{ $slot }}

        <p class="auth-foot">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
      </div>
    </main>
  </div>

  <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/app-shell.js') }}?v={{ @filemtime(public_path('assets/js/app-shell.js')) }}"></script>
</body>
</html>
