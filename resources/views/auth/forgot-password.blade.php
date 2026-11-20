<x-guest-layout title="Reset password">
    <h1>Reset your password</h1>
    <p class="lead-text">Enter the email you sign in with and we'll send you a link to choose a new password.</p>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Send reset link</button>

        <p class="text-center small mt-4 mb-0">
            <a href="{{ route('login') }}" class="fw-medium"><i class="bi bi-arrow-left me-1"></i>Back to sign in</a>
        </p>
    </form>
</x-guest-layout>
