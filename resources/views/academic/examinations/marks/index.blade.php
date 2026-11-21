<x-app-layout>
<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Marks Entry') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="panel mb-4">
        <div class="panel-body p-4">
            <form action="{{ route('academic.marks.index') }}" method="GET" class="row g-3 align-items-end" id="marks-filter">
                <div class="col-md-3">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">Select Exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Class <span class="text-muted">(filter)</span></label>
                    <select name="school_class_id" class="form-select">
                        <option value="">All classes</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section <span class="text-muted">(filter)</span></label>
                    <select name="section_id" class="form-select" {{ request('school_class_id') ? '' : 'disabled' }}>
                        <option value="">All sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Subject</label>
                    <select name="exam_subject_id" class="form-select" {{ request('exam_id') ? '' : 'disabled' }}>
                        <option value="">Select Subject</option>
                        @foreach($examSubjects as $subj)
                            <option value="{{ $subj->id }}" {{ request('exam_subject_id') == $subj->id ? 'selected' : '' }}>
                                {{ $subj->subject->name ?? 'Subject' }} — {{ $subj->schoolClass->name ?? '' }}{{ $subj->section ? ' · ' . $subj->section->name : ' · all sections' }} (Max {{ rtrim(rtrim($subj->max_marks, '0'), '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Load</button>
                </div>
            </form>

            @if(request()->filled('exam_id') && $examSubjects->isEmpty())
                <div class="empty-note mt-3">
                    <i class="bi bi-journal-x" aria-hidden="true"></i>
                    No subjects {{ request()->filled('school_class_id') || request()->filled('section_id') ? 'match this filter' : 'are scheduled in this exam yet' }}.
                    <div class="mt-2"><a href="{{ route('academic.exams.show', request('exam_id')) }}" class="btn btn-sm btn-light"><i class="bi bi-calendar-plus"></i> Schedule subjects on this exam</a></div>
                </div>
            @elseif(request()->filled('exam_id') && ! request()->filled('exam_subject_id') && $examSubjects->isNotEmpty())
                <p class="small text-muted mt-3 mb-0">{{ $examSubjects->count() }} {{ \Illuminate\Support\Str::plural('subject', $examSubjects->count()) }} to mark. Choose one to enter marks.</p>
            @endif
        </div>
    </div>

    @if(request()->filled('exam_subject_id') && $selectedSubject)
    <div class="panel">
        <div class="panel-body p-4">
            <div class="panel-header d-flex flex-wrap justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h6 mb-0">
                        {{ $selectedSubject->subject->name ?? 'Subject' }} — {{ $selectedSubject->schoolClass->name ?? '' }}@if($selectedSubject->section) · Section {{ $selectedSubject->section->name }}@elseif(request()->filled('section_id') && ! $showSection) · Section {{ optional($roster->first())->section->name ?? '' }}@else · all sections @endif
                    </h2>
                    <span class="small text-muted">Max marks {{ rtrim(rtrim($selectedSubject->max_marks, '0'), '.') }} · Pass {{ rtrim(rtrim($selectedSubject->passing_marks, '0'), '.') }}</span>
                </div>
                <span class="badge bg-secondary">{{ $roster->count() }} {{ \Illuminate\Support\Str::plural('student', $roster->count()) }}</span>
            </div>
            <form action="{{ route('academic.marks.store') }}" method="POST">
                @csrf
                <input type="hidden" name="exam_subject_id" value="{{ $selectedSubject->id }}">

                @if($roster->isEmpty())
                    <div class="empty-note"><i class="bi bi-people" aria-hidden="true"></i>No active students enrolled in this class/section for the current session.</div>
                @else
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:70px">Roll</th>
                                <th>Student</th>
                                @if($showSection)<th>Section</th>@endif
                                <th>Marks Obtained</th>
                                <th>Absent</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roster as $enrollment)
                                @php
                                    $student = $enrollment->student;
                                    $mark = $existingMarks[$student->id] ?? null;
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $enrollment->roll_number ?: '—' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="initials">{{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->last_name, 0, 1) }}</span>
                                            <div>
                                                <div class="fw-semibold">{{ $student->full_name }}</div>
                                                <small class="text-muted">{{ $student->admission_number }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    @if($showSection)<td>{{ $enrollment->section->name ?? '—' }}</td>@endif
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
    // Changing exam/class/section reloads (so the subject list and sections refresh);
    // changing subject loads the roster. The Section options are rendered server-side.
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('marks-filter');
        const subjectSelect = form.querySelector('select[name="exam_subject_id"]');
        ['exam_id', 'school_class_id', 'section_id'].forEach(function (name) {
            const el = form.querySelector('select[name="' + name + '"]');
            if (el) el.addEventListener('change', function () { subjectSelect.value = ''; form.submit(); });
        });
        subjectSelect.addEventListener('change', function () { if (subjectSelect.value) form.submit(); });
    });
</script>
@endpush
</x-app-layout>
