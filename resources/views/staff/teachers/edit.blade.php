<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Edit Teacher') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" >
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ route('staff.teachers.update', $teacher) }}">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">First Name *</label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $firstName ?? '') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Last Name *</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $lastName ?? '') }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $teacher->user->phone ?? '') }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" value="{{ old('employee_id', $teacher->employee_id ?? '') }}">
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Department</label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                            <option value="">Select Department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id', $teacher->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Designation</label>
                        <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror">
                            <option value="">Select Designation</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" {{ old('designation_id', $teacher->designation_id) == $designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Joining Date</label>
                        <input type="date" name="joining_date" class="form-control @error('joining_date') is-invalid @enderror" value="{{ old('joining_date', optional($teacher->joining_date)->format('Y-m-d')) }}">
                        @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-medium">Status *</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $teacher->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $teacher->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="on_leave" {{ old('status', $teacher->status) == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            <option value="resigned" {{ old('status', $teacher->status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-medium">Qualification</label>
                        <textarea name="qualification" class="form-control @error('qualification') is-invalid @enderror">{{ old('qualification', $teacher->qualification ?? '') }}</textarea>
                        @error('qualification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
        
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-medium">Subjects</label>
                        <select name="subjects[]" multiple class="form-select @error('subjects') is-invalid @enderror">
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ (is_array(old('subjects')) && in_array($subject->id, old('subjects'))) || (!is_array(old('subjects')) && isset($teacher) && $teacher->subjects->contains($subject->id)) ? 'selected' : '' }}>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple</div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label fw-medium">Classes</label>
                        <select name="classes[]" multiple class="form-select @error('classes') is-invalid @enderror">
                            @foreach($classes as $school_class)
                                <option value="{{ $school_class->id }}" {{ (is_array(old('classes')) && in_array($school_class->id, old('classes'))) || (!is_array(old('classes')) && isset($teacher) && $teacher->classes->contains($school_class->id)) ? 'selected' : '' }}>{{ $school_class->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple</div>
                    </div>
            
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('staff.teachers.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
