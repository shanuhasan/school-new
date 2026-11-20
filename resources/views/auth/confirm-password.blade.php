<x-guest-layout title="Confirm password">
    <h1>Confirm it's you</h1>
    <p class="lead-text">This area holds sensitive records. Please re-enter your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="password-field">
                <input id="password" type="password" name="password"
                       class="form-control @error('password') is-invalid @enderror" required autofocus autocomplete="current-password">
                <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Confirm</button>
    </form>
</x-guest-layout>
