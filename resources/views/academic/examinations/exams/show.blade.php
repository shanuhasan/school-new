<x-app-layout>
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
                            <label class="form-label">Section (Optional)</label>
                            <select name="section_id" class="form-select">
                                <option value="">All Sections</option>
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
                            <label class="form-label">Starts (date &amp; time)</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" class="form-control @error('starts_at') is-invalid @enderror" required>
                            @error('starts_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ends (date &amp; time)</label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" class="form-control @error('ends_at') is-invalid @enderror" required>
                            @error('ends_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Max Marks</label>
                                <input type="number" step="0.01" name="max_marks" value="{{ old('max_marks', 100) }}" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Passing Marks</label>
                                <input type="number" step="0.01" name="passing_marks" value="{{ old('passing_marks', 40) }}" class="form-control" required>
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
                                    <th>Class / Section</th>
                                    <th>Subject</th>
                                    <th>Schedule</th>
                                    <th>Marks (Pass/Max)</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exam->subjects as $subj)
                                    <tr>
                                        <td>{{ $subj->schoolClass->name }}{{ $subj->section ? ' · ' . $subj->section->name : ' · all sections' }}</td>
                                        <td>{{ $subj->subject->name }}</td>
                                        <td>
                                            @if($subj->starts_at)
                                                <div class="fw-medium">{{ $subj->starts_at->format('D, d M Y') }}</div>
                                                <small class="text-muted">{{ $subj->starts_at->format('g:i A') }}@if($subj->ends_at) – {{ $subj->ends_at->isSameDay($subj->starts_at) ? $subj->ends_at->format('g:i A') : $subj->ends_at->format('d M, g:i A') }}@endif</small>
                                            @elseif($subj->exam_date)
                                                {{ $subj->exam_date->format('M d, Y') }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ rtrim(rtrim($subj->passing_marks, '0'), '.') }} / {{ rtrim(rtrim($subj->max_marks, '0'), '.') }}</td>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.querySelector('select[name="school_class_id"]');
        const sectionSelect = document.querySelector('select[name="section_id"]');
        
        function loadSections(classId, selectedSection = null) {
            if (!classId) {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                return;
            }
            
            fetch(`/academic/classes/${classId}/sections`)
                .then(res => res.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    data.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        if (selectedSection && selectedSection == section.id) {
                            option.selected = true;
                        }
                        sectionSelect.appendChild(option);
                    });
                });
        }

        if (classSelect) {
            classSelect.addEventListener('change', function() {
                loadSections(this.value);
            });
            
            const currentClass = classSelect.value;
            const currentSection = "{{ old('section_id') }}";
            if (currentClass) {
                loadSections(currentClass, currentSection);
            }
        }
    });
</script>
@endpush
</x-app-layout>
