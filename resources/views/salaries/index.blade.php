<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Finance</span><h1 class="h3 mb-1">Salaries</h1></div>
            @can('salary.manage')
            <a href="{{ route('salaries.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New payslip</a>
            @endcan
        </div></div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <section class="stat-grid" aria-label="Salary summary">
        <div class="stat">
            <div class="stat-label"><i class="bi bi-cash-stack"></i>Paid out</div>
            <div class="stat-value">{{ number_format($summary['paid'], 2) }}</div>
            <div class="stat-meta">total net salary paid</div>
        </div>
        <div class="stat">
            <div class="stat-label"><i class="bi bi-hourglass-split"></i>Pending</div>
            <div class="stat-value">{{ $summary['pending'] }}</div>
            <div class="stat-meta">payslips not yet paid</div>
        </div>
    </section>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Employee…" class="form-control" style="max-width:220px">
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control" style="max-width:170px">
                    <select name="status" class="form-select" style="max-width:150px">
                        <option value="">All</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    </select>
                    <button class="btn btn-dark">Filter</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Employee</th><th>Month</th><th>Net salary</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($salaries as $s)
                            <tr>
                                <td><div class="fw-semibold">{{ $s->employee_name }}</div><small class="text-muted">{{ $s->designation ?? '—' }}</small></td>
                                <td>{{ $s->month_label }}</td>
                                <td class="tabular fw-semibold">{{ number_format($s->net_salary, 2) }}</td>
                                <td><span class="badge {{ $s->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($s->status) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('salaries.show', $s) }}" class="btn btn-sm btn-info" title="Open"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('salaries.slip', $s) }}" target="_blank" class="btn btn-sm btn-light" title="Print payslip"><i class="bi bi-printer"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No payslips yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $salaries->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</x-app-layout>
