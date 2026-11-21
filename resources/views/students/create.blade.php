<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Add New Student') }}</h1></div></div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 800px;">
        <div class="panel-body p-3 p-lg-4">
            <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <h5 class="mb-3 text-primary border-bottom pb-2">Academic Information</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Admission Number <span class="text-danger">*</span></label>
                        <input type="text" name="admission_number" class="form-control @error('admission_number') is-invalid @enderror" value="{{ old('admission_number') }}" required>
                        @error('admission_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Admission Date <span class="text-danger">*</span></label>
                        <input type="date" name="admission_date" class="form-control @error('admission_date') is-invalid @enderror" value="{{ old('admission_date', date('Y-m-d')) }}" required>
                        @error('admission_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h5 class="mb-3 text-primary border-bottom pb-2">Personal Details</h5>
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
                    
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                            <option value="">Select</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group') == $bg ? 'selected' : '' }}>{{ $bg }}</option>
                            @endforeach
                        </select>
                        @error('blood_group') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h5 class="mb-3 text-primary border-bottom pb-2">Contact & Media</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                        <div class="form-text">Max size: 2MB.</div>
                        @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h5 class="mb-3 mt-2 text-primary border-bottom pb-2">Enrollment <span class="text-muted small fw-normal">(optional)</span></h5>
                @php $currentSession = ($sessions ?? collect())->firstWhere('is_current', true); @endphp
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Session</label>
                        <select name="academic_session_id" id="enroll_session" class="form-select @error('academic_session_id') is-invalid @enderror">
                            <option value="">Not enrolled</option>
                            @foreach(($sessions ?? []) as $s)
                                <option value="{{ $s->id }}" {{ old('academic_session_id', optional($currentSession)->id) == $s->id ? 'selected' : '' }}>{{ $s->name }}{{ $s->is_current ? ' (current)' : '' }}</option>
                            @endforeach
                        </select>
                        @error('academic_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <select name="school_class_id" id="enroll_class" class="form-select @error('school_class_id') is-invalid @enderror">
                            <option value="">Select class</option>
                            @foreach(($classes ?? []) as $c)
                                <option value="{{ $c->id }}" {{ old('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select name="section_id" id="enroll_section" class="form-select @error('section_id') is-invalid @enderror" data-selected="{{ old('section_id') }}">
                            <option value="">Select class first</option>
                        </select>
                        @error('section_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Roll number</label>
                        <input type="text" name="roll_number" class="form-control @error('roll_number') is-invalid @enderror" value="{{ old('roll_number') }}">
                        @error('roll_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12"><div class="form-text mt-0">Pick a session and class to enrol the student now. You can also do this later from their profile.</div></div>
                </div>
                <script type="application/json" id="enroll-sections-data">
                    {!! json_encode(($classes ?? collect())->mapWithKeys(fn ($c) => [$c->id => $c->sections->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
                </script>

                <h5 class="mb-3 mt-2 text-primary border-bottom pb-2">Login account</h5>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="create_login" value="1" id="create_login" @checked(old('create_login'))>
                    <label class="form-check-label" for="create_login">Create a login account so this student can sign in</label>
                </div>
                <div class="row g-3 mb-2" id="login-fields">
                    <div class="col-md-6">
                        <label class="form-label">Password <span class="text-muted">(optional)</span></label>
                        <input type="text" name="login_password" class="form-control @error('login_password') is-invalid @enderror" placeholder="Leave blank to auto-generate" value="{{ old('login_password') }}">
                        @error('login_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Requires the email above. If blank, a temporary password is generated and shown once.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </div>
            </form>
            @push('scripts')
            <script>
                (function () {
                    var cb = document.getElementById('create_login'), box = document.getElementById('login-fields');
                    function sync() { box.style.display = cb.checked ? '' : 'none'; }
                    cb.addEventListener('change', sync); sync();

                    // Enrollment: fill Section from the chosen Class
                    var classSel = document.getElementById('enroll_class');
                    var sectionSel = document.getElementById('enroll_section');
                    var data = JSON.parse(document.getElementById('enroll-sections-data').textContent || '{}');
                    function fillSections(selected) {
                        var list = data[classSel.value] || [];
                        sectionSel.innerHTML = '';
                        var first = document.createElement('option');
                        first.value = '';
                        first.textContent = !classSel.value ? 'Select class first' : (list.length ? 'All / none' : 'No sections');
                        sectionSel.appendChild(first);
                        list.forEach(function (s) {
                            var o = document.createElement('option');
                            o.value = s.id; o.textContent = s.name;
                            if (String(s.id) === String(selected)) o.selected = true;
                            sectionSel.appendChild(o);
                        });
                    }
                    classSel.addEventListener('change', function () { fillSections(''); });
                    fillSections(sectionSel.getAttribute('data-selected'));
                })();
            </script>
            @endpush
        </div>
    </div>
</x-app-layout>
