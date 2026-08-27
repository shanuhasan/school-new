import os

views_dir = 'resources/views/academic/examinations'

marks_index = """<x-app-layout>
<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Marks Entry') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="panel mb-4">
        <div class="panel-body p-4">
            <form action="{{ route('academic.marks.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">Select Exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="school_class_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subject</label>
                    <select name="exam_subject_id" class="form-select">
                        <option value="">Select Subject</option>
                        @foreach($examSubjects as $subj)
                            <option value="{{ $subj->id }}" {{ request('exam_subject_id') == $subj->id ? 'selected' : '' }}>{{ $subj->subject->name }} (Max: {{ $subj->max_marks }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Load</button>
                </div>
            </form>
        </div>
    </div>

    @if(request()->filled('exam_subject_id'))
    <div class="panel">
        <div class="panel-body p-4">
            <form action="{{ route('academic.marks.store') }}" method="POST">
                @csrf
                <input type="hidden" name="exam_subject_id" value="{{ request('exam_subject_id') }}">
                
                <div class="table-responsive mb-4">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Marks Obtained</th>
                                <th>Absent</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $mark = $existingMarks[$student->id] ?? null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <div class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                    {{ substr($student->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $student->name }}</h6>
                                                <small class="text-muted">{{ $student->studentProfile->admission_number ?? '' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width: 150px;">
                                        <input type="number" step="0.01" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm" value="{{ $mark ? $mark->marks_obtained : '' }}" max="100" min="0">
                                    </td>
                                    <td style="width: 100px;" class="text-center">
                                        <input type="checkbox" name="marks[{{ $student->id }}][is_absent]" value="1" class="form-check-input" {{ $mark && $mark->is_absent ? 'checked' : '' }}>
                                    </td>
                                    <td>
                                        <input type="text" name="marks[{{ $student->id }}][remarks]" class="form-control form-control-sm" value="{{ $mark ? $mark->remarks : '' }}" placeholder="Optional">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-success px-5"><i class="bi bi-save"></i> Save Marks</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
</x-app-layout>
"""
with open(f'{views_dir}/marks/index.blade.php', 'w') as f: f.write(marks_index)

results_index = """<x-app-layout>
<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Result Processing') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="panel mb-4">
        <div class="panel-body p-4">
            <form action="{{ route('academic.results.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">Select Exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="school_class_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Section (Optional)</label>
                    <select name="section_id" class="form-select">
                        <option value="">Select Section</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Load Results</button>
                </div>
            </form>
        </div>
    </div>

    @if(request()->filled(['exam_id', 'school_class_id']))
    <div class="panel">
        <div class="panel-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Class Results</h5>
                <div>
                    <form action="{{ route('academic.results.process') }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="exam_id" value="{{ request('exam_id') }}">
                        <input type="hidden" name="school_class_id" value="{{ request('school_class_id') }}">
                        <input type="hidden" name="section_id" value="{{ request('section_id') }}">
                        <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat"></i> Process/Recalculate Results</button>
                    </form>
                    
                    @if(count($results) > 0 && !$results[0]->is_published)
                        <form action="{{ route('academic.results.publish') }}" method="POST" class="d-inline ms-2">
                            @csrf
                            <input type="hidden" name="exam_id" value="{{ request('exam_id') }}">
                            <input type="hidden" name="school_class_id" value="{{ request('school_class_id') }}">
                            <input type="hidden" name="section_id" value="{{ request('section_id') }}">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Once published, marks cannot be modified. Are you sure?')"><i class="bi bi-check2-circle"></i> Publish Results</button>
                        </form>
                    @endif
                </div>
            </div>

            @if(count($results) > 0)
                <div class="alert {{ $results[0]->is_published ? 'alert-success' : 'alert-info' }}">
                    Status: <strong>{{ $results[0]->is_published ? 'Published' : 'Not Published' }}</strong>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $res)
                                <tr>
                                    <td>{{ $res->student->name }}</td>
                                    <td>{{ $res->total_obtained_marks }} / {{ $res->total_max_marks }}</td>
                                    <td>{{ $res->percentage }}%</td>
                                    <td><strong>{{ $res->grade->name ?? 'N/A' }}</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $res->status == 'pass' ? 'success' : 'danger' }}">{{ strtoupper($res->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>No results processed yet. Click "Process/Recalculate Results" to generate them.</p>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
</x-app-layout>
"""
with open(f'{views_dir}/results/index.blade.php', 'w') as f: f.write(results_index)

print("Generated marks and results views")
