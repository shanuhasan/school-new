<x-guest-layout title="Sign in">
    <h1>Welcome back</h1>
    <p class="lead-text">Sign in with your school email to continue.</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="name@school.edu" required autofocus autocomplete="username">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-baseline">
                <label for="password" class="form-label">Password</label>
                @if (Route::has('password.request'))
                    <a class="small fw-medium" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <div class="password-field">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       required autocomplete="current-password">
                <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-4">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label small">Keep me signed in on this device</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Sign in</button>

        @if (Route::has('register'))
            <p class="text-center small text-muted mt-4 mb-0">
                New to the system? <a href="{{ route('register') }}" class="fw-medium">Create an account</a>
            </p>
        @endif
    </form>
</x-guest-layout>
