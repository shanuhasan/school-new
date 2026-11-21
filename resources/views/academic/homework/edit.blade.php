<x-app-layout>

@section('title', 'Edit Homework')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Homework</h4>
        <a href="{{ route('academic.homework.show', $homework) }}" class="btn btn-secondary">Back to Details</a>
    </div>

    <div class="panel">
        <div class="panel-body p-4">
            <form action="{{ route('academic.homework.update', $homework) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="school_class_id" id="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ (old('school_class_id') ?? $homework->school_class_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Section</label>
                        <select name="section_id" id="section_id" class="form-select">
                            <option value="" {{ (old('section_id', $homework->section_id) == '' || old('section_id', $homework->section_id) === null) ? 'selected' : '' }}>All sections</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}" {{ (string) old('section_id', $homework->section_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">“All sections” sets this for the whole class.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ (old('subject_id') ?? $homework->subject_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $homework->title) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Homework Date <span class="text-danger">*</span></label>
                        <input type="date" name="homework_date" class="form-control" value="{{ old('homework_date', $homework->homework_date->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $homework->due_date->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" required>{{ old('description', $homework->description) }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Update Attachment (Optional)</label>
                        <input type="file" name="attachment" class="form-control">
                        @if($homework->attachment_path)
                            <div class="form-text text-warning">Uploading a new file will replace the current attachment.</div>
                        @endif
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
