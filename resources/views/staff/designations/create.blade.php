<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Create Designation') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 600px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ route('staff.designations.store') }}">
                @csrf
                
                <div class="mb-3">
                    <label for="name" class="form-label fw-medium">{{ __('Name') }}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="description" class="form-label fw-medium">{{ __('Description') }}</label>
                    <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
        
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('staff.designations.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
