<x-guest-layout title="Verify email">
    <h1>Check your inbox</h1>
    <p class="lead-text">We've sent a verification link to your email address. Open it to activate your account.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="status">A new verification link is on its way to your inbox.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-muted">Sign out</button>
    </form>
</x-guest-layout>
