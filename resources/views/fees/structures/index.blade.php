<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Finance · Setup</span><h1 class="h3 mb-1">Class-wise fees</h1>
                <p>Set a fee per class, then generate each student's monthly / quarterly / yearly installments.</p></div>
            <div class="d-flex gap-2">
                @can('fees.create')<a href="{{ route('fees.structures.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add class fee</a>@endcan
                <a href="{{ route('fees.students') }}" class="btn btn-light"><i class="bi bi-people"></i> Student fees</a>
            </div>
        </div></div>
    </x-slot>

    @foreach(['success' => 'success', 'error' => 'danger'] as $key => $type)
        @if(session($key))<div class="alert alert-{{ $type }} alert-dismissible fade show">{{ session($key) }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @endforeach

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <form method="GET" class="d-flex gap-2">
                    <select name="academic_session_id" class="form-select" style="min-width:200px" onchange="this.form.submit()">
                        @foreach($sessions as $s)<option value="{{ $s->id }}" @selected($sessionId == $s->id)>{{ $s->name }}{{ $s->is_current ? ' (current)' : '' }}</option>@endforeach
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Class</th><th>Fee</th><th>Frequency</th><th class="text-end">Per period</th><th>Installments</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($structures as $st)
                            <tr>
                                <td class="fw-semibold">{{ $st->schoolClass?->name }}</td>
                                <td>{{ $st->title }}</td>
                                <td><span class="badge bg-secondary">{{ \App\Models\FeeStructure::FREQUENCIES[$st->frequency] ?? $st->frequency }}</span> <small class="text-muted">{{ $st->periodCount() }} periods</small></td>
                                <td class="text-end tabular">{{ number_format($st->amountPerPeriod(), 2) }}</td>
                                <td>
                                    @php $g = $generated[$st->id] ?? 0; @endphp
                                    @if($g > 0)<span class="text-success">{{ $g }} generated</span>@else<span class="text-muted">none yet</span>@endif
                                </td>
                                <td class="text-end">
                                    @can('fees.create')
                                    <form method="POST" action="{{ route('fees.structures.generate', $st) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success btn-text" title="Create installments for all students"><i class="bi bi-arrow-repeat"></i> Generate</button>
                                    </form>
                                    <form method="POST" action="{{ route('fees.structures.destroy', $st) }}" class="d-inline" onsubmit="return confirm('Remove this class fee? Unpaid installments will be deleted.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No class fees set for this session. Click <strong>Add class fee</strong>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> Generate creates one fee row per student per period. It skips students who already have theirs, so you can safely run it again after admitting new students.</p>
        </div>
    </div>
</x-app-layout>
