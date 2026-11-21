@php
    $badge = ['paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'unpaid' => 'bg-danger', 'waived' => 'bg-info'];
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

    <section class="stat-grid mb-4" aria-label="Fee summary">
        <div class="stat"><div class="stat-label"><i class="bi bi-receipt"></i>Billed</div><div class="stat-value">{{ number_format($totals['billed'], 2) }}</div></div>
        <div class="stat"><div class="stat-label"><i class="bi bi-check2-circle"></i>Paid</div><div class="stat-value">{{ number_format($totals['paid'], 2) }}</div></div>
        <div class="stat"><div class="stat-label"><i class="bi bi-exclamation-circle"></i>Balance</div><div class="stat-value {{ $totals['balance'] > 0 ? 'text-danger' : '' }}">{{ number_format($totals['balance'], 2) }}</div></div>
    </section>

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
                            <tr><th>Period</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th class="text-end">Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach($fees as $fee)
                                <tr>
                                    <td class="fw-medium">{{ $fee->period ?: $fee->title }}</td>
                                    <td><small class="text-muted">{{ optional($fee->due_date)->format('d M Y') ?? '—' }}</small></td>
                                    <td class="text-end tabular">{{ number_format($fee->payable, 2) }}</td>
                                    <td class="text-end tabular">{{ number_format($fee->paid_amount, 2) }}</td>
                                    <td class="text-end tabular">{{ number_format($fee->balance, 2) }}</td>
                                    <td><span class="badge {{ $badge[$fee->status] ?? 'bg-secondary' }}">{{ ucfirst($fee->status) }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('fees.slip', $fee) }}" target="_blank" class="btn btn-sm btn-light" title="Print slip"><i class="bi bi-printer"></i></a>
                                        @can('fees.collect')
                                            @if($fee->balance > 0)
                                            <a href="{{ route('fees.show', $fee) }}" class="btn btn-sm btn-success btn-text"><i class="bi bi-cash"></i> Collect</a>
                                            @else
                                            <a href="{{ route('fees.show', $fee) }}" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                                            @endif
                                        @endcan
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
</x-app-layout>
