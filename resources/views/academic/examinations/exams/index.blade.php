<x-app-layout>
<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Manage Exams') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-body p-4">
                    <h5 class="mb-3">Create Exam</h5>
                    <form action="{{ route('academic.exams.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Exam Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Midterm 2026">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Exam Type</label>
                            <select name="exam_type_id" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach($examTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create Exam</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="panel">
                <div class="panel-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exams as $exam)
                                    <tr>
                                        <td><strong>{{ $exam->name }}</strong></td>
                                        <td>{{ $exam->examType->name ?? 'N/A' }}</td>
                                        <td>{{ $exam->start_date->format('M d') }} - {{ $exam->end_date->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('academic.exams.show', $exam) }}" class="btn btn-sm btn-info text-white"><i class="bi bi-gear"></i> Manage Subjects</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
