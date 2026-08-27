<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Student Attendance Report') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mb-4">
        <div class="panel-body p-3 p-lg-4">
            <form method="GET" action="{{ route('reports.attendance.student') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Student ID / Name</label>
                        <!-- For simplicity in UI, we use an input. In production, this might be a select2 or search modal -->
                        <input type="number" name="student_id" class="form-control" placeholder="Enter Student ID" value="{{ request('student_id') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Month (Optional)</label>
                        <select name="month" class="form-select">
                            <option value="">All Months</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ request('month') == $i ? 'selected' : '' }}>
                                    {{ date("F", mktime(0, 0, 0, $i, 10)) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">View Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    @if($student)
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold">{{ $student->first_name }} {{ $student->last_name }}</h5>
                        <p class="text-muted mb-1">Admission No: {{ $student->admission_number }}</p>
                        
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Present:</span>
                                <strong>{{ $summary['present'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Absent:</span>
                                <strong>{{ $summary['absent'] }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Late / Half Day:</span>
                                <strong>{{ $summary['late'] }} / {{ $summary['half_day'] }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Attendance %:</span>
                                <strong class="{{ $summary['percentage'] < 75 ? 'text-danger' : 'text-success' }}">{{ $summary['percentage'] }}%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="panel h-100">
                    <div class="panel-body p-3 p-lg-4">
                        <h5 class="mb-3">Attendance History</h5>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table align-middle">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($attendances as $att)
                                        <tr>
                                            <td>{{ $att->date->format('d M Y, D') }}</td>
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
                                            <td colspan="3" class="text-center">No attendance records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
