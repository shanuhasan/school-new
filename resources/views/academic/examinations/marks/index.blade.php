<x-app-layout>
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
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select name="section_id" class="form-select">
                        <option value="">All / Optional</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="exam_subject_id" class="form-select">
                        <option value="">Select Subject</option>
                        @foreach($examSubjects as $subj)
                            <option value="{{ $subj->id }}" {{ request('exam_subject_id') == $subj->id ? 'selected' : '' }}>
                                {{ $subj->subject->name ?? 'Subject' }}@if($subj->section) · {{ $subj->section->name }}@endif (Max: {{ rtrim(rtrim($subj->max_marks, '0'), '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Load</button>
                </div>
            </form>

            @if(request()->filled(['exam_id', 'school_class_id']) && $examSubjects->isEmpty())
                <div class="empty-note mt-3">
                    <i class="bi bi-journal-x" aria-hidden="true"></i>
                    No subjects are scheduled for this class in this exam yet.
                    <div class="mt-2"><a href="{{ route('academic.exams.show', request('exam_id')) }}" class="btn btn-sm btn-light"><i class="bi bi-calendar-plus"></i> Schedule subjects on this exam</a></div>
                </div>
            @elseif(request()->filled('exam_id') && ! request()->filled('exam_subject_id') && $examSubjects->isNotEmpty())
                <p class="small text-muted mt-3 mb-0">Choose a subject above and press <strong>Load</strong> to enter marks.</p>
            @endif
        </div>
    </div>

    @if(request()->filled('exam_subject_id') && $selectedSubject)
    <div class="panel">
        <div class="panel-body p-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h6 mb-0">{{ $selectedSubject->subject->name ?? 'Subject' }} — {{ $selectedSubject->schoolClass->name ?? '' }}@if($selectedSubject->section) · Section {{ $selectedSubject->section->name }}@endif</h2>
                    <span class="small text-muted">Max marks {{ rtrim(rtrim($selectedSubject->max_marks, '0'), '.') }} · Pass {{ rtrim(rtrim($selectedSubject->passing_marks, '0'), '.') }}</span>
                </div>
                <span class="badge bg-secondary">{{ $students->count() }} {{ \Illuminate\Support\Str::plural('student', $students->count()) }}</span>
            </div>
            <form action="{{ route('academic.marks.store') }}" method="POST">
                @csrf
                <input type="hidden" name="exam_subject_id" value="{{ request('exam_subject_id') }}">

                @if($students->isEmpty())
                    <div class="empty-note"><i class="bi bi-people" aria-hidden="true"></i>No active students enrolled in this class/section for the current session.</div>
                @else
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
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="initials">{{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->last_name, 0, 1) }}</span>
                                            <div>
                                                <div class="fw-semibold">{{ $student->full_name }}</div>
                                                <small class="text-muted">{{ $student->admission_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width: 150px;">
                                        <input type="number" step="0.01" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm" value="{{ $mark ? rtrim(rtrim($mark->marks_obtained, '0'), '.') : '' }}" max="{{ $selectedSubject->max_marks }}" min="0">
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
                @endif
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.querySelector('select[name="school_class_id"]');
        const sectionSelect = document.querySelector('select[name="section_id"]');
        
        function loadSections(classId, selectedSection = null) {
            if (!classId) {
                sectionSelect.innerHTML = '<option value="">All / Optional</option>';
                return;
            }
            
            fetch(`/academic/classes/${classId}/sections`)
                .then(res => res.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All / Optional</option>';
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

        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });

        // Pre-load on edit/validation fail
        const currentClass = classSelect.value;
        const currentSection = "{{ request('section_id') }}";
        if (currentClass) {
            loadSections(currentClass, currentSection);
        }
    });
</script>
@endpush
</x-app-layout>
