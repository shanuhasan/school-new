<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Daily Attendance Report') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mb-4">
        <div class="panel-body p-3 p-lg-4">
            <form method="GET" action="{{ route('reports.attendance.daily') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class (Optional)</label>
                        <select name="school_class_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">View Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Marked</h5>
                    <h3>{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Present</h5>
                    <h3>{{ $summary['present'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Absent</h5>
                    <h3>{{ $summary['absent'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Late / Half Day</h5>
                    <h3>{{ $summary['late'] + $summary['half_day'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class & Section</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $att)
                            <tr>
                                <td class="fw-semibold">{{ $att->student->first_name }} {{ $att->student->last_name }}</td>
                                <td>{{ $att->schoolClass->name }} ({{ $att->section->name }})</td>
                                <td>
                                    @if($att->status == 'present') <span class="badge bg-success">Present</span>
                                    @elseif($att->status == 'absent') <span class="badge bg-danger">Absent</span>
                                    @elseif($att->status == 'late') <span class="badge bg-warning">Late</span>
                                    @elseif($att->status == 'half_day') <span class="badge bg-info">Half Day</span>
                                    @endif
                                </td>
                                <td>{{ $att->remarks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No attendance records found for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
