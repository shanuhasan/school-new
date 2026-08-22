<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ isset($session) ? __('Edit Session') : __('Create Session') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 600px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ isset($session) ? route('academic.sessions.update', $session) : route('academic.sessions.store') }}">
                @csrf
                @if(isset($session))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label fw-medium">{{ __('Name (e.g. 2026-2027)') }}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $session->name ?? '') }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="start_date" class="form-label fw-medium">{{ __('Start Date') }}</label>
                    <input id="start_date" type="date" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date', isset($session) ? $session->start_date->format('Y-m-d') : '') }}" required>
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="end_date" class="form-label fw-medium">{{ __('End Date') }}</label>
                    <input id="end_date" type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date', isset($session) ? $session->end_date->format('Y-m-d') : '') }}" required>
                    @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="is_current" value="1" id="is_current" {{ old('is_current', $session->is_current ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_current">
                        {{ __('Mark as Current Session') }}
                    </label>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academic.sessions.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($session) ? __('Update') : __('Create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
