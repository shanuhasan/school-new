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

                @isset($subjects)
                    @php
                        $selected = collect(old('subjects', $class->subjects->pluck('id')->all()))->map(fn ($v) => (int) $v)->all();
                        $optional = collect(old('optional', $class->subjects->where('pivot.type', 'optional')->pluck('id')->all()))->map(fn ($v) => (int) $v)->all();
                    @endphp
                    <input type="hidden" name="subjects_present" value="1">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <label class="form-label fw-medium mb-0">{{ __('Subjects') }}</label>
                            <a href="{{ route('academic.subjects.create') }}" class="small">New subject</a>
                        </div>
                        @error('subjects.*') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

                        @if($subjects->isEmpty())
                            <div class="empty-note border rounded">No subjects yet. <a href="{{ route('academic.subjects.create') }}">Create subjects</a> first.</div>
                        @else
                            <div class="border rounded" style="max-height: 320px; overflow-y: auto">
                                @foreach($subjects as $subject)
                                    @php $on = in_array($subject->id, $selected, true); @endphp
                                    <div class="d-flex align-items-center gap-2 px-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                        <input class="form-check-input mt-0 subject-toggle" type="checkbox" name="subjects[]" value="{{ $subject->id }}" id="subject-{{ $subject->id }}" @checked($on)>
                                        <label class="form-check-label me-auto" for="subject-{{ $subject->id }}">
                                            {{ $subject->name }} @if($subject->code)<span class="text-muted small">· {{ $subject->code }}</span>@endif
                                        </label>
                                        <div class="form-check form-switch mb-0" title="Optional subject">
                                            <input class="form-check-input subject-optional" type="checkbox" role="switch" name="optional[]" value="{{ $subject->id }}"
                                                   id="optional-{{ $subject->id }}" @checked(in_array($subject->id, $optional, true)) @disabled(! $on)>
                                            <label class="form-check-label small text-muted" for="optional-{{ $subject->id }}">Optional</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text">Tick the subjects taught in this class. Switch on “Optional” for electives; the rest are compulsory.</div>
                        @endif
                    </div>
                @endisset

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ isset($class) ? route('academic.classes.show', $class) : route('academic.classes.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($class) ? __('Update') : __('Create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        // "Optional" only makes sense for a ticked subject
        document.querySelectorAll('.subject-toggle').forEach(function (box) {
            box.addEventListener('change', function () {
                var sw = document.getElementById('optional-' + box.value);
                if (!sw) return;
                sw.disabled = !box.checked;
                if (!box.checked) sw.checked = false;
            });
        });
    </script>
    @endpush
</x-app-layout>
