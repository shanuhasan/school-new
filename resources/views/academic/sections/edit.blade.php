<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ isset($section) ? __('Edit Section') : __('Create Section') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 600px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ isset($section) ? route('academic.sections.update', $section) : route('academic.sections.store') }}">
                @csrf
                @if(isset($section))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="school_class_id" class="form-label fw-medium">{{ __('Class') }}</label>
                    <select id="school_class_id" name="school_class_id" class="form-select @error('school_class_id') is-invalid @enderror" required>
                        <option value="">Select a Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ (old('school_class_id', $section->school_class_id ?? '') == $class->id) ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_class_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="name" class="form-label fw-medium">{{ __('Section Name (e.g. A, B)') }}</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $section->name ?? '') }}" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academic.sections.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($section) ? __('Update') : __('Create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
