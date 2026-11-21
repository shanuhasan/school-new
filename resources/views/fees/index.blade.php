@php
    $badge = ['paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'unpaid' => 'bg-danger'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div><span class="eyebrow">Finance</span><h1 class="h3 mb-1">Fees</h1></div>
                <div class="d-flex gap-2">
                    <a href="{{ route('fees.report') }}" class="btn btn-light"><i class="bi bi-bar-chart-line"></i> Reports</a>
                    @can('fees.create')
                    <a href="{{ route('fees.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New fee</a>
                    @endcan
                </div>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <section class="stat-grid" aria-label="Fees summary">
        <div class="stat">
            <div class="stat-label"><i class="bi bi-cash-coin"></i>Outstanding</div>
            <div class="stat-value">{{ number_format(max(0, $summary['outstanding']), 2) }}</div>
            <div class="stat-meta">{{ $summary['unpaid'] }} unpaid / partial</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-check2-circle"></i>Collected</div>
            <div class="stat-value">{{ number_format($summary['collected'], 2) }}</div>
            <div class="stat-meta">total received</div>
        </div>
    </section>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Student or title…" class="form-control" style="max-width:240px">
                    <select name="status" class="form-select" style="max-width:160px">
                        <option value="">All statuses</option>
                        @foreach(['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid'] as $k => $v)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-dark">Search</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th>Fee</th><th>Payable</th><th>Paid</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($fees as $fee)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $fee->student?->full_name }}</div>
                                    <small class="text-muted">{{ $fee->student?->admission_number }}</small>
                                </td>
                                <td>{{ $fee->title }}<br><small class="text-muted">Due {{ optional($fee->due_date)->format('d M Y') ?? '—' }}</small></td>
                                <td class="tabular">{{ number_format($fee->payable, 2) }}</td>
                                <td class="tabular">{{ number_format($fee->paid_amount, 2) }}</td>
                                <td class="tabular fw-semibold">{{ number_format($fee->balance, 2) }}</td>
                                <td><span class="badge {{ $badge[$fee->status] ?? 'bg-secondary' }}">{{ ucfirst($fee->status) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('fees.show', $fee) }}" class="btn btn-sm btn-info" title="Open"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('fees.slip', $fee) }}" target="_blank" class="btn btn-sm btn-light" title="Print slip"><i class="bi bi-printer"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No fees yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $fees->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</x-app-layout>
