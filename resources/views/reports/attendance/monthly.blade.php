<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Monthly Attendance Report') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mb-4">
        <div class="panel-body p-3 p-lg-4">
            <form method="GET" action="{{ route('reports.attendance.monthly') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select" required>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ request('month', date('m')) == $i ? 'selected' : '' }}>
                                    {{ date("F", mktime(0, 0, 0, $i, 10)) }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select" required>
                            @for($i = date('Y')-2; $i <= date('Y'); $i++)
                                <option value="{{ $i }}" {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Class</label>
                        <select name="school_class_id" class="form-select" required onchange="this.form.submit();">
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ request('school_class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Section</label>
                        <select name="section_id" class="form-select" required>
                            <option value="">Select</option>
                            @if(request('school_class_id'))
                                @foreach(\App\Models\SchoolClass::find(request('school_class_id'))->sections as $sec)
                                    <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">View</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    @if(request('school_class_id') && request('section_id'))
        <div class="panel">
            <div class="panel-body p-3 p-lg-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th class="text-start" style="min-width: 150px;">Student Name</th>
                                @for($d = 1; $d <= $daysInMonth; $d++)
                                    <th style="width: 20px;">{{ $d }}</th>
                                @endfor
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $data)
                                <tr>
                                    <td class="text-start fw-semibold">{{ $data['student']->first_name }} {{ $data['student']->last_name }}</td>
                                    @for($d = 1; $d <= $daysInMonth; $d++)
                                        @php $st = $data['days'][$d]; @endphp
                                        @if($st == 'present') <td class="bg-success text-white">P</td>
                                        @elseif($st == 'absent') <td class="bg-danger text-white">A</td>
                                        @elseif($st == 'late') <td class="bg-warning text-dark">L</td>
                                        @elseif($st == 'half_day') <td class="bg-info text-dark">H</td>
                                        @else <td>-</td>
                                        @endif
                                    @endfor
                                    <td class="fw-bold">{{ $data['percentage'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $daysInMonth + 2 }}">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
