<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <h1 class="h3 mb-1">
                        @if(isset($student))
                            Add Guardian for {{ $student->full_name }}
                        @else
                            Add New Guardian
                        @endif
                    </h1>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 800px;">
        <div class="panel-body p-3 p-lg-4">
            <form action="{{ route('guardians.store') }}" method="POST">
                @csrf
                
                @if(isset($student))
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                        <div>This guardian will be automatically linked to <strong>{{ $student->full_name }}</strong>.</div>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="occupation" class="form-control @error('occupation') is-invalid @enderror" value="{{ old('occupation') }}">
                        @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    @if(isset($student))
                    <div class="col-md-6">
                        <label class="form-label">Relationship to Student <span class="text-danger">*</span></label>
                        <select name="relationship" class="form-select @error('relationship') is-invalid @enderror" required>
                            <option value="">Select...</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Uncle">Uncle</option>
                            <option value="Aunt">Aunt</option>
                            <option value="Grandparent">Grandparent</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="is_emergency_contact">
                            <label class="form-check-label text-danger" for="is_emergency_contact">
                                <i class="bi bi-heart-pulse-fill"></i> Primary Emergency Contact
                            </label>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    @if(isset($student))
                        <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">Cancel</a>
                    @else
                        <a href="{{ route('guardians.index') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                    <button type="submit" class="btn btn-primary">Save Guardian</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
