@php
    $dateObj = \Illuminate\Support\Carbon::parse($date);
    $alreadyMarked = $existingAttendance->isNotEmpty();
    $statuses = [
        'present' => ['Present', 'P'],
        'late' => ['Late', 'L'],
        'half_day' => ['Half day', 'H'],
        'absent' => ['Absent', 'A'],
    ];
    $initials = fn ($s) => mb_substr($s->first_name, 0, 1) . mb_substr($s->last_name, 0, 1);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Attendance</span>
                    <h1 class="h3 mb-1">{{ $schoolClass->name }} · Section {{ $section->name }}</h1>
                    <p>{{ $dateObj->format('l, j F Y') }}{{ $dateObj->isToday() ? ' (today)' : '' }}</p>
                </div>
                <div>
                    <a href="{{ route('academic.attendance.index', ['school_class_id' => $schoolClass->id, 'section_id' => $section->id, 'date' => $date]) }}" class="btn btn-light">
                        <i class="bi bi-sliders"></i> Change class or date
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            @if($enrollments->isEmpty())
                <div class="empty-note">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    No students are enrolled in {{ $schoolClass->name }} – {{ $section->name }} for the current session.
                    <div class="mt-2"><a href="{{ route('academic.classes.show', $schoolClass) }}">Open the class</a></div>
                </div>
            @else
                <form method="POST" action="{{ route('academic.attendance.store') }}" id="attendance-form">
                    @csrf
                    <input type="hidden" name="school_class_id" value="{{ $schoolClass->id }}">
                    <input type="hidden" name="section_id" value="{{ $section->id }}">
                    <input type="hidden" name="date" value="{{ $date }}">

                    @if($alreadyMarked)
                        <div class="alert alert-info py-2">
                            <div>Attendance for this day was already saved. Changes you make here will update it.</div>
                        </div>
                    @endif

                    <div class="att-toolbar">
                        <div class="att-tally" aria-live="polite">
                            <span><i style="background:var(--success)"></i>Present <b data-count="present">0</b></span>
                            <span><i style="background:var(--accent)"></i>Late <b data-count="late">0</b></span>
                            <span><i style="background:var(--info)"></i>Half day <b data-count="half_day">0</b></span>
                            <span><i style="background:var(--danger)"></i>Absent <b data-count="absent">0</b></span>
                            <span>of <b>{{ $enrollments->count() }}</b></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-light" data-mark-all="present"><i class="bi bi-check2-all"></i> All present</button>
                            <button type="button" class="btn btn-sm btn-light" data-mark-all="absent"><i class="bi bi-x-lg"></i> All absent</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0 att-table">
                            <thead>
                                <tr>
                                    <th style="width:70px">Roll</th>
                                    <th>Student</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enrollment)
                                    @php
                                        $sid = $enrollment->student_id;
                                        $record = $existingAttendance->get($sid);
                                        $status = $record ? $record->status : 'present'; // default present
                                        $remarks = $record ? $record->remarks : '';
                                    @endphp
                                    <tr data-status="{{ $status }}">
                                        <td class="text-muted d-none d-md-table-cell">{{ $enrollment->roll_number ?? '—' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="initials">{{ $initials($enrollment->student) }}</span>
                                                <div>
                                                    <div class="fw-semibold">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</div>
                                                    <div class="small text-muted d-md-none">Roll {{ $enrollment->roll_number ?? '—' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="att-toggle" role="radiogroup" aria-label="Attendance for {{ $enrollment->student->first_name }}">
                                                @foreach($statuses as $value => [$label, $short])
                                                    <input type="radio" class="btn-check" name="attendance[{{ $sid }}][status]" id="status_{{ strtolower($short) }}_{{ $sid }}" value="{{ $value }}" autocomplete="off" @checked($status === $value)>
                                                    <label class="s-{{ $value }}" for="status_{{ strtolower($short) }}_{{ $sid }}" title="{{ $label }}">{{ $label }}</label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[{{ $sid }}][remarks]" class="form-control form-control-sm" value="{{ $remarks }}" placeholder="Optional remarks" aria-label="Remarks for {{ $enrollment->student->first_name }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="att-savebar">
                        <span class="hint">Everyone starts as present. Tap a status to change it.</span>
                        <button type="submit" class="btn btn-primary" id="attendance-save">
                            <i class="bi bi-check2"></i> {{ $alreadyMarked ? 'Update attendance' : 'Save attendance' }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var form = document.getElementById('attendance-form');
            if (!form) return;

            function refresh() {
                var counts = { present: 0, late: 0, half_day: 0, absent: 0 };
                form.querySelectorAll('tbody tr').forEach(function (row) {
                    var checked = row.querySelector('input[type=radio]:checked');
                    var status = checked ? checked.value : '';
                    row.setAttribute('data-status', status);
                    if (counts[status] !== undefined) counts[status]++;
                });
                Object.keys(counts).forEach(function (k) {
                    var el = form.querySelector('[data-count="' + k + '"]');
                    if (el) el.textContent = counts[k];
                });
            }

            form.addEventListener('change', function (e) {
                if (e.target.type === 'radio') refresh();
            });

            form.querySelectorAll('[data-mark-all]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var value = btn.getAttribute('data-mark-all');
                    form.querySelectorAll('input[type=radio][value="' + value + '"]').forEach(function (r) { r.checked = true; });
                    refresh();
                });
            });

            form.addEventListener('submit', function () {
                var btn = document.getElementById('attendance-save');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving…';
            });

            refresh();
        })();
    </script>
    @endpush
</x-app-layout>
