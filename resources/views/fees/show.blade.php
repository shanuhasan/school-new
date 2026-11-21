@php
    $badge = ['paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'unpaid' => 'bg-danger', 'waived' => 'bg-info'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Fee</span><h1 class="h3 mb-1">{{ $fee->title }}</h1></div>
            <div>
                <a href="{{ route('fees.slip', $fee) }}" target="_blank" class="btn btn-light"><i class="bi bi-printer"></i> Print slip</a>
                <a href="{{ route('fees.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div></div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="fw-semibold fs-5">{{ $fee->student?->full_name }}</div>
                            <div class="small text-muted">{{ $fee->student?->admission_number }}{{ $fee->session ? ' · ' . $fee->session->name : '' }}</div>
                        </div>
                        <span class="badge {{ $badge[$fee->status] ?? 'bg-secondary' }}">{{ ucfirst($fee->status) }}</span>
                    </div>
                    <table class="table mb-0">
                        <tbody>
                            <tr><td>Amount</td><td class="text-end tabular">{{ number_format($fee->amount, 2) }}</td></tr>
                            @if($fee->discount > 0)<tr><td>Discount</td><td class="text-end tabular text-success">− {{ number_format($fee->discount, 2) }}</td></tr>@endif
                            @if($fee->waived_amount > 0)<tr><td>Waived</td><td class="text-end tabular text-info">− {{ number_format($fee->waived_amount, 2) }}</td></tr>@endif
                            @if($fee->fine > 0)<tr><td>Fine</td><td class="text-end tabular text-danger">+ {{ number_format($fee->fine, 2) }}</td></tr>@endif
                            <tr class="fw-semibold"><td>Payable</td><td class="text-end tabular">{{ number_format($fee->payable, 2) }}</td></tr>
                            <tr><td>Paid</td><td class="text-end tabular">{{ number_format($fee->paid_amount, 2) }}</td></tr>
                            <tr class="fw-bold"><td>Balance</td><td class="text-end tabular">{{ number_format($fee->balance, 2) }}</td></tr>
                        </tbody>
                    </table>
                    @if($fee->due_date)<p class="small text-muted mt-2 mb-0">Due {{ $fee->due_date->format('d M Y') }}@if($fee->paid_on) · Last paid {{ $fee->paid_on->format('d M Y') }}@endif</p>@endif
                    @if($fee->remarks)<p class="small text-muted mt-2 mb-0">{{ $fee->remarks }}</p>@endif
                    @if($fee->waive_reason)<p class="small text-info mt-2 mb-0"><i class="bi bi-patch-check"></i> Waiver: {{ $fee->waive_reason }}</p>@endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @can('fees.collect')
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Record payment</h2></div>
                    @if($fee->balance <= 0)
                        <div class="empty-note"><i class="bi bi-check2-circle"></i>Fully paid. Nothing outstanding.</div>
                    @else
                        <form method="POST" action="{{ route('fees.collect', $fee) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="{{ $fee->balance }}" name="amount" value="{{ number_format($fee->balance, 2, '.', '') }}" class="form-control @error('amount') is-invalid @enderror" required>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">Balance: {{ number_format($fee->balance, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select">
                                    @foreach(['Cash', 'Online', 'Cheque', 'Card', 'Bank Transfer'] as $m)
                                        <option value="{{ $m }}" @selected($fee->method === $m)>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-success w-100"><i class="bi bi-cash"></i> Record payment</button>
                        </form>
                    @endif
                </div>
            </div>
            @endcan

            @can('fees.refund')
            @if($fee->balance > 0)
            <div class="panel mt-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Waive fee</h2></div>
                    <p class="small text-muted">Reduce what the student owes (full or part), e.g. a concession or scholarship.</p>
                    <form method="POST" action="{{ route('fees.waive', $fee) }}" onsubmit="return confirm('Waive this amount? It reduces the balance without a payment.')">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Amount to waive <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $fee->balance }}" name="amount" value="{{ number_format($fee->balance, 2, '.', '') }}" class="form-control @error('amount') is-invalid @enderror" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Balance: {{ number_format($fee->balance, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <input type="text" name="reason" value="{{ old('reason') }}" class="form-control @error('reason') is-invalid @enderror" placeholder="e.g. Sibling concession, scholarship" required>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-outline-info w-100"><i class="bi bi-patch-check"></i> Waive fee</button>
                    </form>
                </div>
            </div>
            @endif
            @endcan
        </div>
    </div>
</x-app-layout>
