<x-app-layout>

<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Homework') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Homework Management</h4>
        @can('create', App\Models\Homework::class)
            <a href="{{ route('academic.homework.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create Homework</a>
        @endcan
    </div>

    <div class="panel mb-4">
        <div class="panel-body p-3">
            <form action="{{ route('academic.homework.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="school_class_id" class="form-select">
                        <option value="">All Classes</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('academic.homework.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @forelse($homeworks as $hw)
            <div class="col">
                <div class="card h-100 border-0 shadow-sm" style="background-color: var(--bs-body-bg);">
                    <div class="card-header bg-primary text-white p-3 border-0 rounded-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-light text-primary">{{ $hw->subject->name }}</span>
                            <small>{{ $hw->homework_date->format('d M, Y') }}</small>
                        </div>
                        <h5 class="card-title mt-3 mb-0">{{ $hw->title }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Class</small>
                            <span class="fw-semibold">{{ $hw->schoolClass->name }} ({{ $hw->section->name ?? 'All sections' }})</span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Due Date</small>
                            <span class="fw-semibold text-danger"><i class="bi bi-calendar-event"></i> {{ $hw->due_date->format('d M, Y') }}</span>
                        </div>
                        <p class="card-text text-truncate">{{ $hw->description }}</p>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted"><i class="bi bi-person"></i> {{ $hw->teacher->name }}</small>
                        <a href="{{ route('academic.homework.show', $hw) }}" class="btn btn-sm btn-outline-primary">View Details</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                    <h5>No homework found</h5>
                </div>
            </div>
        @endforelse
    </div>
    
    <div class="mt-4">
        {{ $homeworks->links() }}
    </div>
</div>
</x-app-layout>
