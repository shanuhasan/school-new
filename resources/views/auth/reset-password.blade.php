<x-guest-layout title="Choose a new password">
    <h1>Choose a new password</h1>
    <p class="lead-text">Use at least 8 characters. You'll be signed in afterwards.</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New password</label>
            <div class="password-field">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control @error('password_confirmation') is-invalid @enderror" required autocomplete="new-password">
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Reset password</button>
    </form>
</x-guest-layout>
