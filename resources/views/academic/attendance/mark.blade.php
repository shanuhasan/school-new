<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Mark Attendance') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">{{ $schoolClass->name }} - {{ $section->name }} | Date: {{ $date }}</h5>
                <a href="{{ route('academic.attendance.index') }}" class="btn btn-light border">Change Filter</a>
            </div>

            @if($enrollments->isEmpty())
                <div class="alert alert-info">No students enrolled in this class and section.</div>
            @else
                <form method="POST" action="{{ route('academic.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="school_class_id" value="{{ $schoolClass->id }}">
                    <input type="hidden" name="section_id" value="{{ $section->id }}">
                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Roll No</th>
                                    <th>Student Name</th>
                                    <th>Attendance Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enrollment)
                                    @php
                                        $record = $existingAttendance->get($enrollment->student_id);
                                        $status = $record ? $record->status : 'present'; // default present
                                        $remarks = $record ? $record->remarks : '';
                                    @endphp
                                    <tr>
                                        <td>{{ $enrollment->roll_number ?? '-' }}</td>
                                        <td class="fw-semibold">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check" name="attendance[{{ $enrollment->student_id }}][status]" id="status_p_{{ $enrollment->student_id }}" value="present" {{ $status == 'present' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-success btn-sm" for="status_p_{{ $enrollment->student_id }}">Present</label>

                                                <input type="radio" class="btn-check" name="attendance[{{ $enrollment->student_id }}][status]" id="status_l_{{ $enrollment->student_id }}" value="late" {{ $status == 'late' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-warning btn-sm" for="status_l_{{ $enrollment->student_id }}">Late</label>

                                                <input type="radio" class="btn-check" name="attendance[{{ $enrollment->student_id }}][status]" id="status_h_{{ $enrollment->student_id }}" value="half_day" {{ $status == 'half_day' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-info btn-sm" for="status_h_{{ $enrollment->student_id }}">Half Day</label>
                                                
                                                <input type="radio" class="btn-check" name="attendance[{{ $enrollment->student_id }}][status]" id="status_a_{{ $enrollment->student_id }}" value="absent" {{ $status == 'absent' ? 'checked' : '' }}>
                                                <label class="btn btn-outline-danger btn-sm" for="status_a_{{ $enrollment->student_id }}">Absent</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[{{ $enrollment->student_id }}][remarks]" class="form-control form-control-sm" value="{{ $remarks }}" placeholder="Optional remarks">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
