@php
    $badge = ['paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'unpaid' => 'bg-danger', 'waived' => 'bg-info'];
    $canCollect = auth()->user()->can('fees.collect');
    $canWaive = auth()->user()->can('fees.refund');
    $bulk = $canCollect || $canWaive;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Fee ledger</span><h1 class="h3 mb-1">{{ $student->full_name }}</h1>
                <p>{{ $student->admission_number }}</p></div>
            <div class="d-flex gap-2">
                @can('fees.create')<a href="{{ route('fees.create', ['student_id' => $student->id]) }}" class="btn btn-light"><i class="bi bi-plus-lg"></i> One-off fee</a>@endcan
                <a href="{{ route('fees.students') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div></div>
    </x-slot>

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @error('reason')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('paid_on')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <section class="stat-grid mb-4" aria-label="Fee summary">
        <div class="stat"><div class="stat-label"><i class="bi bi-receipt"></i>Billed</div><div class="stat-value">{{ number_format($totals['billed'], 2) }}</div></div>
        <div class="stat"><div class="stat-label"><i class="bi bi-check2-circle"></i>Paid</div><div class="stat-value">{{ number_format($totals['paid'], 2) }}</div></div>
        <div class="stat"><div class="stat-label"><i class="bi bi-exclamation-circle"></i>Balance</div><div class="stat-value {{ $totals['balance'] > 0 ? 'text-danger' : '' }}">{{ number_format($totals['balance'], 2) }}</div></div>
    </section>

    <form method="POST" id="fee-bulk-form">
        @csrf
        @if($bulk && $groups->isNotEmpty())
            <div class="panel mb-3 d-print-none">
                <div class="panel-body p-3 d-flex flex-wrap align-items-end gap-2">
                    <div class="me-auto">
                        <div class="fw-semibold"><i class="bi bi-check2-square"></i> Pay / waive many at once</div>
                        <div class="small text-muted">Tick the months (e.g. 3–4 months' advance), then choose an action.</div>
                        <div class="small"><button type="button" class="btn btn-link btn-sm p-0" data-select-due>Select all due</button> · <span id="sel-count">0</span> selected</div>
                    </div>
                    @if($canCollect)
                    <div>
                        <label class="form-label mb-1">Date</label>
                        <input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control form-control-sm" style="max-width:150px">
                    </div>
                    <div>
                        <label class="form-label mb-1">Method</label>
                        <select name="method" class="form-select form-select-sm" style="max-width:130px">
                            @foreach(['Cash', 'Online', 'Cheque', 'Card', 'Bank Transfer'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                        </select>
                    </div>
                    <button class="btn btn-success btn-sm" formaction="{{ route('fees.bulk-collect') }}" onclick="return confirmBulk('collect')"><i class="bi bi-cash-stack"></i> Collect selected</button>
                    @endif
                    @if($canWaive)
                    <div>
                        <label class="form-label mb-1">Waive reason</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control form-control-sm" style="max-width:180px" placeholder="e.g. Concession">
                    </div>
                    <button class="btn btn-outline-info btn-sm" formaction="{{ route('fees.bulk-waive') }}" onclick="return confirmBulk('waive')"><i class="bi bi-patch-check"></i> Waive selected</button>
                    @endif
                </div>
            </div>
        @endif

        @forelse($groups as $label => $fees)
            @php
                $head = $fees->first();
                $gBilled = $fees->sum(fn ($f) => $f->payable);
                $gPaid = $fees->sum(fn ($f) => (float) $f->paid_amount);
            @endphp
            <div class="panel mb-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">{{ $head->title }} @if($head->schoolClass)<span class="text-muted fw-normal">· {{ $head->schoolClass->name }}</span>@endif</h2>
                        <span class="small text-muted">Paid {{ number_format($gPaid, 2) }} / {{ number_format($gBilled, 2) }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-compact align-middle mb-0">
                            <thead>
                                <tr>
                                    @if($bulk)<th style="width:34px" class="d-print-none"><input type="checkbox" class="form-check-input" data-group-check></th>@endif
                                    <th>Period</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th class="text-end d-print-none">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fees as $fee)
                                    <tr>
                                        @if($bulk)
                                        <td class="d-print-none">
                                            @if($fee->balance > 0)
                                                <input type="checkbox" class="form-check-input fee-check" name="fee_ids[]" value="{{ $fee->id }}" data-balance="{{ $fee->balance }}">
                                            @endif
                                        </td>
                                        @endif
                                        <td class="fw-medium">{{ $fee->period ?: $fee->title }}</td>
                                        <td><small class="text-muted">{{ optional($fee->due_date)->format('d M Y') ?? '—' }}</small></td>
                                        <td class="text-end tabular">{{ number_format($fee->payable, 2) }}</td>
                                        <td class="text-end tabular">{{ number_format($fee->paid_amount, 2) }}</td>
                                        <td class="text-end tabular">{{ number_format($fee->balance, 2) }}</td>
                                        <td><span class="badge {{ $badge[$fee->status] ?? 'bg-secondary' }}">{{ ucfirst($fee->status) }}</span></td>
                                        <td class="text-end d-print-none">
                                            <a href="{{ route('fees.slip', $fee) }}" target="_blank" class="btn btn-sm btn-light" title="Print slip"><i class="bi bi-printer"></i></a>
                                            @if($canCollect)
                                                <a href="{{ route('fees.show', $fee) }}" class="btn btn-sm {{ $fee->balance > 0 ? 'btn-success' : 'btn-info' }}" title="{{ $fee->balance > 0 ? 'Collect / part pay' : 'View' }}"><i class="bi bi-{{ $fee->balance > 0 ? 'cash' : 'eye' }}"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="panel"><div class="panel-body p-3 p-lg-4">
                <div class="empty-note"><i class="bi bi-cash-coin"></i>No fees for this student yet.
                    Set a <a href="{{ route('fees.structures.index') }}">class-wise fee</a> and Generate, or add a one-off fee.</div>
            </div></div>
        @endforelse
    </form>

    @push('scripts')
    <script>
        (function () {
            var form = document.getElementById('fee-bulk-form');
            if (!form) return;
            var boxes = function () { return Array.prototype.slice.call(form.querySelectorAll('.fee-check')); };
            var countEl = document.getElementById('sel-count');

            function refresh() {
                var sel = boxes().filter(function (b) { return b.checked; });
                var total = sel.reduce(function (s, b) { return s + parseFloat(b.getAttribute('data-balance') || 0); }, 0);
                if (countEl) countEl.textContent = sel.length + (sel.length ? ' · ' + total.toFixed(2) : '');
            }
            form.addEventListener('change', function (e) { if (e.target.classList.contains('fee-check')) refresh(); });

            form.querySelectorAll('[data-group-check]').forEach(function (gc) {
                gc.addEventListener('change', function () {
                    gc.closest('table').querySelectorAll('.fee-check').forEach(function (b) { b.checked = gc.checked; });
                    refresh();
                });
            });
            form.querySelectorAll('[data-select-due]').forEach(function (btn) {
                btn.addEventListener('click', function () { boxes().forEach(function (b) { b.checked = true; }); refresh(); });
            });

            window.confirmBulk = function (kind) {
                var sel = boxes().filter(function (b) { return b.checked; });
                if (!sel.length) { alert('Select at least one month first.'); return false; }
                if (kind === 'waive') {
                    var r = form.querySelector('[name="reason"]');
                    if (r && !r.value.trim()) { alert('Enter a waive reason.'); r.focus(); return false; }
                    return confirm('Waive ' + sel.length + ' selected installment(s)?');
                }
                return confirm('Collect ' + sel.length + ' selected installment(s)?');
            };
            refresh();
        })();
    </script>
    @endpush
</x-app-layout>
