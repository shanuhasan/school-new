<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ isset($class) ? __('Edit Class') : __('Create Class') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 600px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ isset($class) ? route('academic.classes.update', $class) : route('academic.classes.store') }}">
                @csrf
                @if(isset($class))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label fw-medium">{{ __('Name') }}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $class->name ?? '') }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="level" class="form-label fw-medium">{{ __('Level (Numeric Order)') }}</label>
                    <input id="level" type="number" class="form-control @error('level') is-invalid @enderror" name="level" value="{{ old('level', $class->level ?? '') }}">
                    @error('level')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academic.classes.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($class) ? __('Update') : __('Create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
