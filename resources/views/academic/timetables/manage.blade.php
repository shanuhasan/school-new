<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Class Timetable') }}</h1></div></div></div>
    </x-slot>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <h5 class="fw-bold mb-3">Add Period for {{ $schoolClass->name }} ({{ $section->name }})</h5>
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('academic.timetables.store') }}">
                        @csrf
                        <input type="hidden" name="school_class_id" value="{{ $schoolClass->id }}">
                        <input type="hidden" name="section_id" value="{{ $section->id }}">

                        <div class="mb-3">
                            <label class="form-label">Day of Week</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Select Day</option>
                                @foreach($days as $num => $dayName)
                                    <option value="{{ $num }}" {{ old('day_of_week') == $num ? 'selected' : '' }}>{{ $dayName }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }} ({{ $subject->code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Teacher</label>
                            <select name="teacher_id" class="form-select" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Room Number (Optional)</label>
                            <input type="text" name="room_number" class="form-control" value="{{ old('room_number') }}">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Add Period</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Weekly Timetable</h5>
                        <a href="{{ route('academic.timetables.index') }}" class="btn btn-secondary btn-sm">Change Class</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead>
                                <tr>
                                    <th style="width: 15%">Day</th>
                                    <th>Periods</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($days as $num => $dayName)
                                    <tr>
                                        <td class="fw-bold">{{ $dayName }}</td>
                                        <td class="text-start">
                                            @if($timetables->has($num))
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($timetables[$num] as $period)
                                                        <div class="card border border-primary shadow-sm" style="min-width: 150px; background-color: var(--bs-body-bg);">
                                                            <div class="card-body p-2 position-relative">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <small class="fw-bold text-primary">{{ \Carbon\Carbon::parse($period->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('H:i') }}</small>
                                                                    <form action="{{ route('academic.timetables.destroy', $period) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this period?');">
                                                                        @csrf @method('DELETE')
                                                                        <button class="btn btn-sm text-danger p-0 border-0 bg-transparent"><i class="bi bi-x-circle-fill"></i></button>
                                                                    </form>
                                                                </div>
                                                                <div class="fw-semibold mb-1">{{ $period->subject->name }}</div>
                                                                <small class="d-block text-muted"><i class="bi bi-person"></i> {{ $period->teacher->name }}</small>
                                                                @if($period->room_number)
                                                                    <small class="d-block text-muted"><i class="bi bi-geo-alt"></i> Room: {{ $period->room_number }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted fst-italic">No periods scheduled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
