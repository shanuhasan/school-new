<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ isset($subject) ? __('Edit Subject') : __('Create Subject') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 600px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ isset($subject) ? route('academic.subjects.update', $subject) : route('academic.subjects.store') }}">
                @csrf
                @if(isset($subject))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label fw-medium">{{ __('Name') }}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $subject->name ?? '') }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="code" class="form-label fw-medium">{{ __('Subject Code (Optional)') }}</label>
                    <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $subject->code ?? '') }}">
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academic.subjects.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($subject) ? __('Update') : __('Create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
