<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Manage Timetable') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 800px;">
        <div class="panel-body p-3 p-lg-4">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('academic.timetables.manage') }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Class</label>
                        <select name="school_class_id" class="form-select" required onchange="this.form.action='{{ route('academic.timetables.index') }}'; this.form.submit();">
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Section</label>
                        <select name="section_id" class="form-select" required>
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary">View Timetable</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
