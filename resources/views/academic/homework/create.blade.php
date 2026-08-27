<x-app-layout>

<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Create Homework') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Create Homework</h4>
        <a href="{{ route('academic.homework.index') }}" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="panel">
        <div class="panel-body p-4">
            <form action="{{ route('academic.homework.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="school_class_id" id="class_id" class="form-select @error('school_class_id') is-invalid @enderror" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Section <span class="text-danger">*</span></label>
                        <select name="section_id" id="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
                            <option value="">Select Section</option>
                        </select>
                        @error('section_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Homework Date <span class="text-danger">*</span></label>
                        <input type="date" name="homework_date" class="form-control @error('homework_date') is-invalid @enderror" value="{{ old('homework_date', date('Y-m-d')) }}" required>
                        @error('homework_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="5" required>{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
                        <div class="form-text">Max size: 10MB</div>
                        @error('attachment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4">Create Homework</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('class_id').addEventListener('change', function() {
    let classId = this.value;
    let sectionSelect = document.getElementById('section_id');
    sectionSelect.innerHTML = '<option value="">Loading...</option>';
    
    if(classId) {
        fetch(`/academic/classes/${classId}/sections`)
            .then(res => res.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                data.forEach(section => {
                    sectionSelect.innerHTML += `<option value="${section.id}">${section.name}</option>`;
                });
            })
            .catch(err => {
                console.error(err);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            });
    } else {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
    }
});
</script>
</x-app-layout>