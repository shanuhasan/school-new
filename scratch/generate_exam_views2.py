import os

views_dir = 'resources/views/academic/examinations'

exams_index = """<x-app-layout>
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
"""
with open(f'{views_dir}/exams/index.blade.php', 'w') as f: f.write(exams_index)

exams_show = """<x-app-layout>
<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ $exam->name }} - Schedule Subjects</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h4>{{ $exam->name }}</h4>
        <a href="{{ route('academic.exams.index') }}" class="btn btn-secondary">Back to Exams</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-body p-4">
                    <h5 class="mb-3">Assign Subject</h5>
                    <form action="{{ route('academic.exams.subjects.assign', $exam) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select name="school_class_id" class="form-select" required>
                                <option value="">Select Class</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject</option>
                                @foreach($subjects as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Exam Date</label>
                            <input type="date" name="exam_date" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Max Marks</label>
                                <input type="number" step="0.01" name="max_marks" value="100" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Passing Marks</label>
                                <input type="number" step="0.01" name="passing_marks" value="40" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Assign Subject</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="panel">
                <div class="panel-body p-4">
                    <h5 class="mb-3">Scheduled Subjects</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Marks (Pass/Max)</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exam->subjects as $subj)
                                    <tr>
                                        <td>{{ $subj->schoolClass->name }}</td>
                                        <td>{{ $subj->subject->name }}</td>
                                        <td>{{ $subj->exam_date->format('M d, Y') }}</td>
                                        <td>{{ $subj->passing_marks }} / {{ $subj->max_marks }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('academic.exams.subjects.destroy', $subj) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></button>
                                            </form>
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
"""
with open(f'{views_dir}/exams/show.blade.php', 'w') as f: f.write(exams_show)

print("Generated exam views")
