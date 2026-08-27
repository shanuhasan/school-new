<x-app-layout>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.querySelector('select[name="school_class_id"]');
        const sectionSelect = document.querySelector('select[name="section_id"]');
        
        function loadSections(classId, selectedSection = null) {
            if (!classId) {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
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
