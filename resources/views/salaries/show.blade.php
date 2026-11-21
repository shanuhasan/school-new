<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Payslip · {{ $salary->month_label }}</span><h1 class="h3 mb-1">{{ $salary->employee_name }}</h1></div>
            <div>
                <a href="{{ route('salaries.slip', $salary) }}" target="_blank" class="btn btn-light"><i class="bi bi-printer"></i> Print payslip</a>
                <a href="{{ route('salaries.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
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
                            <div class="fw-semibold fs-5">{{ $salary->employee_name }}</div>
                            <div class="small text-muted">{{ $salary->designation ?? '—' }} · {{ $salary->month_label }}</div>
                        </div>
                        <span class="badge {{ $salary->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($salary->status) }}</span>
                    </div>
                    <table class="table mb-0">
                        <tbody>
                            <tr><td>Basic</td><td class="text-end tabular">{{ number_format($salary->basic, 2) }}</td></tr>
                            <tr><td>Allowances</td><td class="text-end tabular text-success">+ {{ number_format($salary->allowances, 2) }}</td></tr>
                            <tr><td>Deductions</td><td class="text-end tabular text-danger">− {{ number_format($salary->deductions, 2) }}</td></tr>
                            <tr class="fw-bold"><td>Net salary</td><td class="text-end tabular">{{ number_format($salary->net_salary, 2) }}</td></tr>
                        </tbody>
                    </table>
                    @if($salary->status === 'paid' && $salary->paid_on)<p class="small text-muted mt-2 mb-0">Paid on {{ $salary->paid_on->format('d M Y') }}{{ $salary->method ? ' · ' . $salary->method : '' }}</p>@endif
                    @if($salary->remarks)<p class="small text-muted mt-2 mb-0">{{ $salary->remarks }}</p>@endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @can('salary.manage')
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Payment</h2></div>
                    @if($salary->status === 'paid')
                        <div class="empty-note"><i class="bi bi-check2-circle"></i>Already paid{{ $salary->paid_on ? ' on ' . $salary->paid_on->format('d M Y') : '' }}.</div>
                    @else
                        <form method="POST" action="{{ route('salaries.pay', $salary) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Paid on <span class="text-danger">*</span></label>
                                <input type="date" name="paid_on" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select">
                                    @foreach(['Bank Transfer', 'Cash', 'Cheque', 'Online'] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                </select>
                            </div>
                            <button class="btn btn-success w-100"><i class="bi bi-cash"></i> Mark as paid</button>
                        </form>
                        <form method="POST" action="{{ route('salaries.destroy', $salary) }}" class="mt-2" onsubmit="return confirm('Delete this payslip?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i> Delete payslip</button>
                        </form>
                    @endif
                </div>
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>
