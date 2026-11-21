<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div><span class="eyebrow">Finance · Reports</span><h1 class="h3 mb-1">Fee report — {{ $year }}</h1></div>
                <div class="d-print-none">
                    <button onclick="window.print()" class="btn btn-light"><i class="bi bi-printer"></i> Print</button>
                    <a href="{{ route('fees.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Fees</a>
                </div>
            </div>
        </div>
    </x-slot>

    <form method="GET" class="panel mb-4 d-print-none">
        <div class="panel-body p-3 d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label">Year</label>
                <select name="year" class="form-select" style="min-width:120px" onchange="this.form.submit()">
                    @foreach($years as $y)<option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Session</label>
                <select name="academic_session_id" class="form-select" style="min-width:160px" onchange="this.form.submit()">
                    <option value="">All sessions</option>
                    @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($sessionId == $s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach(['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'] as $k => $v)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-dark">Apply</button>
        </div>
    </form>

    {{-- Summary --}}
    <section class="stat-grid" aria-label="Year summary">
        <div class="stat">
            <div class="stat-label"><i class="bi bi-receipt"></i>Billed ({{ $year }})</div>
            <div class="stat-value">{{ number_format($yearTotal['billed'], 2) }}</div>
            <div class="stat-meta">total payable</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-check2-circle"></i>Collected</div>
            <div class="stat-value">{{ number_format($yearTotal['paid'], 2) }}</div>
            <div class="stat-meta">{{ number_format($collectedThisYear, 2) }} received in {{ $year }}</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-exclamation-circle"></i>Outstanding</div>
            <div class="stat-value">{{ number_format($yearTotal['balance'], 2) }}</div>
            <div class="stat-meta">still due</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-percent"></i>Collection rate</div>
            <div class="stat-value">{{ $yearTotal['billed'] > 0 ? round($yearTotal['paid'] / $yearTotal['billed'] * 100) : 0 }}%</div>
            <div class="stat-meta">paid of billed</div>
        </div>
    </section>

    <div class="row g-4">
        {{-- Month & quarter breakdown --}}
        <div class="col-lg-7">
            <div class="panel h-100">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Monthly &amp; quarterly ({{ $year }})</h2></div>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <thead>
                                <tr><th>Period</th><th class="text-end">Billed</th><th class="text-end">Collected</th><th class="text-end">Outstanding</th></tr>
                            </thead>
                            <tbody>
                                @foreach([1 => [1,2,3], 2 => [4,5,6], 3 => [7,8,9], 4 => [10,11,12]] as $q => $ms)
                                    @foreach($ms as $m)
                                        @php $row = $months[$m]; @endphp
                                        <tr @class(['text-muted' => $row['billed'] == 0 && $row['paid'] == 0])>
                                            <td>{{ $monthName($m) }}</td>
                                            <td class="text-end tabular">{{ number_format($row['billed'], 2) }}</td>
                                            <td class="text-end tabular">{{ number_format($row['paid'], 2) }}</td>
                                            <td class="text-end tabular">{{ number_format($row['balance'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="fw-semibold" style="background:var(--surface-2)">
                                        <td>Q{{ $q }} total</td>
                                        <td class="text-end tabular">{{ number_format($quarters[$q]['billed'], 2) }}</td>
                                        <td class="text-end tabular">{{ number_format($quarters[$q]['paid'], 2) }}</td>
                                        <td class="text-end tabular">{{ number_format($quarters[$q]['balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Year {{ $year }}</td>
                                    <td class="text-end tabular">{{ number_format($yearTotal['billed'], 2) }}</td>
                                    <td class="text-end tabular">{{ number_format($yearTotal['paid'], 2) }}</td>
                                    <td class="text-end tabular">{{ number_format($yearTotal['balance'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Year overview --}}
        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>All years</h2></div>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <thead><tr><th>Year</th><th class="text-end">Billed</th><th class="text-end">Collected</th><th class="text-end">Due</th></tr></thead>
                            <tbody>
                                @forelse($byYear as $y => $t)
                                    <tr>
                                        <td><a href="{{ route('fees.report', array_merge(request()->only('academic_session_id','status'), ['year' => $y])) }}">{{ $y }}</a></td>
                                        <td class="text-end tabular">{{ number_format($t['billed'], 2) }}</td>
                                        <td class="text-end tabular">{{ number_format($t['paid'], 2) }}</td>
                                        <td class="text-end tabular">{{ number_format($t['balance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No fees yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Per-student --}}
    <div class="panel mt-4">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                <h2>Student-wise ({{ $year }})</h2>
                <span class="text-muted small">{{ $byStudent->count() }} students · sorted by amount due</span>
            </div>
            @if($byStudent->isNotEmpty())
                <input type="search" class="form-control form-control-sm mb-3 d-print-none" style="max-width:260px" placeholder="Filter students…" data-table-search="fee-students">
            @endif
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="fee-students">
                    <thead>
                        <tr><th>Student</th><th>Fees</th><th class="text-end">Billed</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr>
                    </thead>
                    <tbody>
                        @forelse($byStudent as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row->student?->full_name }}</div>
                                    <small class="text-muted">{{ $row->student?->admission_number }}</small>
                                </td>
                                <td>{{ $row->count }}</td>
                                <td class="text-end tabular">{{ number_format($row->billed, 2) }}</td>
                                <td class="text-end tabular">{{ number_format($row->paid, 2) }}</td>
                                <td class="text-end tabular fw-semibold {{ $row->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row->balance, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No fees for {{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
