<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Finance</span><h1 class="h3 mb-1">Student fees</h1><p>Track each student's fees — paid and pending.</p></div>
            <a href="{{ route('fees.structures.index') }}" class="btn btn-light"><i class="bi bi-gear"></i> Class-wise fees</a>
        </div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or admission no…" class="form-control" style="max-width:240px">
                    <select name="class_id" class="form-select" style="max-width:170px">
                        <option value="">All classes</option>
                        @foreach($classes as $c)<option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                    <button class="btn btn-dark">Search</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Student</th><th class="text-end">Billed</th><th class="text-end">Paid</th><th class="text-end">Waived</th><th class="text-end">Balance</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($query as $student)
                            @php
                                // Payable already nets off discount and waiver
                                $waived = (float) $student->waived_sum;
                                $payable = (float) $student->billed_sum + (float) $student->fine_sum - (float) $student->discount_sum - $waived;
                                $paid = (float) $student->paid_sum;
                                $balance = round($payable - $paid, 2);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="initials">{{ mb_substr($student->first_name,0,1) }}{{ mb_substr($student->last_name,0,1) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $student->full_name }}
                                                @if($waived > 0)<span class="badge bg-info" title="Part of this student's fee has been waived">Waived</span>@endif
                                            </div>
                                            <small class="text-muted">{{ $student->admission_number }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end tabular">{{ number_format($payable + $waived, 2) }}</td>
                                <td class="text-end tabular">{{ number_format($paid, 2) }}</td>
                                <td class="text-end tabular text-info">{{ $waived > 0 ? number_format($waived, 2) : '—' }}</td>
                                <td class="text-end tabular fw-semibold {{ $balance > 0 ? 'text-danger' : (($payable + $waived) > 0 ? 'text-success' : '') }}">{{ number_format($balance, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('fees.student', $student) }}" class="btn btn-sm btn-outline-primary btn-text"><i class="bi bi-list-check"></i> Fee ledger</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No students found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $query->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</x-app-layout>
