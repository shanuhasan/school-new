@php
    $forStudent = isset($student) && $student;
    $existingGuardians = $existingGuardians ?? collect();
    $duplicateId = session('duplicate_guardian_id');
    $relationships = ['Father', 'Mother', 'Guardian', 'Uncle', 'Aunt', 'Grandparent', 'Other'];

    // Which tab opens first: the one with errors, otherwise "existing" when there is anyone to pick.
    $mode = 'new';
    if ($forStudent && $existingGuardians->isNotEmpty()) {
        $mode = $errors->has('guardian_id') || old('mode') === 'existing' || (! old('mode') && ! $errors->any()) ? 'existing' : 'new';
    }
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    @if($forStudent)
                        <span class="eyebrow">{{ $student->full_name }}</span>
                        <h1 class="h3 mb-1">Add a guardian</h1>
                        <p>Link a parent who is already on file (for example, a sibling's parent) or add someone new.</p>
                    @else
                        <h1 class="h3 mb-1">Add new guardian</h1>
                    @endif
                </div>
                @if($forStudent)
                    <a href="{{ route('students.show', ['student' => $student, 'tab' => 'guardians']) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to student</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 820px;">
        <div class="panel-body p-3 p-lg-4">

            @if($forStudent)
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $mode === 'existing' ? 'active' : '' }}" id="existing-tab" data-bs-toggle="tab" data-bs-target="#existing-pane" type="button" role="tab" aria-controls="existing-pane" aria-selected="{{ $mode === 'existing' ? 'true' : 'false' }}">
                            <i class="bi bi-person-check me-1"></i> Choose existing
                            <span class="badge bg-secondary ms-1">{{ $existingGuardians->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $mode === 'new' ? 'active' : '' }}" id="new-tab" data-bs-toggle="tab" data-bs-target="#new-pane" type="button" role="tab" aria-controls="new-pane" aria-selected="{{ $mode === 'new' ? 'true' : 'false' }}">
                            <i class="bi bi-person-plus me-1"></i> Add new
                        </button>
                    </li>
                </ul>
            @endif

            <div class="tab-content">
                {{-- ===== Link an existing guardian ===== --}}
                @if($forStudent)
                    <div class="tab-pane fade {{ $mode === 'existing' ? 'show active' : '' }}" id="existing-pane" role="tabpanel" aria-labelledby="existing-tab">
                        @if($existingGuardians->isEmpty())
                            <div class="empty-note">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                There are no other guardians on file yet. Use <strong>Add new</strong> to create one.
                            </div>
                        @else
                            <form action="{{ route('students.guardians.store', $student) }}" method="POST">
                                @csrf
                                <input type="hidden" name="mode" value="existing">

                                <label for="guardian-search" class="form-label">Find guardian</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="search" id="guardian-search" class="form-control" placeholder="Search by name, phone or child's name" autocomplete="off">
                                </div>

                                @error('guardian_id') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror

                                <div class="guardian-pick-list" id="guardian-list" role="radiogroup" aria-label="Guardians">
                                    @foreach($existingGuardians as $g)
                                        @php $checked = (string) old('guardian_id', $duplicateId) === (string) $g->id; @endphp
                                        <label class="guardian-pick" data-search="{{ strtolower($g->full_name . ' ' . $g->phone . ' ' . $g->email . ' ' . $g->students->map(fn ($s) => $s->first_name . ' ' . $s->last_name)->implode(' ')) }}">
                                            <input type="radio" name="guardian_id" value="{{ $g->id }}" class="form-check-input" @checked($checked) required>
                                            <span class="initials">{{ mb_substr($g->first_name, 0, 1) }}{{ mb_substr($g->last_name, 0, 1) }}</span>
                                            <span class="guardian-pick-body">
                                                <strong>{{ $g->full_name }}</strong>
                                                <span>
                                                    <i class="bi bi-telephone me-1"></i>{{ $g->phone }}
                                                    @if($g->email) · {{ $g->email }} @endif
                                                </span>
                                                @if($g->students->isNotEmpty())
                                                    <span class="guardian-pick-kids">
                                                        Guardian of {{ $g->students->map(fn ($s) => $s->first_name . ' ' . $s->last_name)->implode(', ') }}
                                                    </span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                    <div class="empty-note d-none" id="guardian-no-match">No guardian matches your search.</div>
                                </div>

                                <div class="row g-3 mt-1 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label" for="existing-relationship">Relationship to {{ $student->first_name }} <span class="text-danger">*</span></label>
                                        <select id="existing-relationship" name="relationship" class="form-select @if(old('mode') === 'existing') @error('relationship') is-invalid @enderror @endif" required>
                                            <option value="">Select…</option>
                                            @foreach($relationships as $r)
                                                <option value="{{ $r }}" @selected(old('mode') === 'existing' && old('relationship') === $r)>{{ $r }}</option>
                                            @endforeach
                                        </select>
                                        @if(old('mode') === 'existing') @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="existing-emergency" @checked(old('mode') === 'existing' && old('is_emergency_contact'))>
                                            <label class="form-check-label" for="existing-emergency">Primary emergency contact</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                                    <a href="{{ route('students.show', ['student' => $student, 'tab' => 'guardians']) }}" class="btn btn-light">Cancel</a>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-link-45deg"></i> Link guardian</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                {{-- ===== Create a new guardian ===== --}}
                <div class="tab-pane fade {{ $mode === 'new' ? 'show active' : '' }}" id="new-pane" role="tabpanel" @if($forStudent) aria-labelledby="new-tab" @endif>
                    <form action="{{ route('guardians.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mode" value="new">

                        @if($forStudent)
                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                            @if($duplicateId)
                                {{-- User has seen the duplicate warning; a second submit creates the record anyway --}}
                                <input type="hidden" name="confirm_duplicate" value="1">
                                <div class="alert alert-warning">
                                    <div>
                                        A guardian with this phone number already exists.
                                        <button type="button" class="btn btn-link p-0 align-baseline fw-semibold" data-switch-to-existing>Link the existing guardian</button>
                                        instead, or press <strong>Save guardian</strong> again to create a separate record.
                                    </div>
                                </div>
                            @else
                                <p class="text-muted small mb-4">The new guardian will be linked to <strong class="text-body">{{ $student->full_name }}</strong>.</p>
                            @endif
                        @endif

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">First name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last name <span class="text-danger">*</span></label>
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

                            @if($forStudent)
                                <div class="col-md-6">
                                    <label class="form-label">Relationship to {{ $student->first_name }} <span class="text-danger">*</span></label>
                                    <select name="relationship" class="form-select @if(old('mode') !== 'existing') @error('relationship') is-invalid @enderror @endif" required>
                                        <option value="">Select…</option>
                                        @foreach($relationships as $r)
                                            <option value="{{ $r }}" @selected(old('mode') !== 'existing' && old('relationship') === $r)>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                    @if(old('mode') !== 'existing') @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_emergency_contact" value="1" id="is_emergency_contact" @checked(old('mode') !== 'existing' && old('is_emergency_contact'))>
                                        <label class="form-check-label" for="is_emergency_contact">Primary emergency contact</label>
                                    </div>
                                </div>
                            @endif

                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="create_login" value="1" id="g_create_login" @checked(old('create_login'))>
                                    <label class="form-check-label" for="g_create_login">Create a login account so this guardian can sign in</label>
                                </div>
                                <div id="g-login-fields">
                                    <label class="form-label">Password <span class="text-muted">(optional)</span></label>
                                    <input type="text" name="login_password" class="form-control @error('login_password') is-invalid @enderror" placeholder="Leave blank to auto-generate" value="{{ old('login_password') }}" style="max-width:360px">
                                    @error('login_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    <div class="form-text">Requires the email above. If blank, a temporary password is generated and shown once.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            @if($forStudent)
                                <a href="{{ route('students.show', ['student' => $student, 'tab' => 'guardians']) }}" class="btn btn-light">Cancel</a>
                            @else
                                <a href="{{ route('guardians.index') }}" class="btn btn-light">Cancel</a>
                            @endif
                            <button type="submit" class="btn btn-primary">Save guardian</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($forStudent)
        @push('scripts')
        <script>
        (function () {
            var search = document.getElementById('guardian-search');
            var items = document.querySelectorAll('#guardian-list .guardian-pick');
            var none = document.getElementById('guardian-no-match');
            if (search) {
                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase(), shown = 0;
                    items.forEach(function (el) {
                        var hit = q === '' || el.getAttribute('data-search').indexOf(q) !== -1;
                        el.classList.toggle('d-none', !hit);
                        if (hit) shown++;
                    });
                    none.classList.toggle('d-none', shown !== 0);
                });
            }
            var checked = document.querySelector('#guardian-list input:checked');
            if (checked) checked.closest('.guardian-pick').scrollIntoView({ block: 'nearest' });

            document.querySelectorAll('[data-switch-to-existing]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = document.getElementById('existing-tab');
                    if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
                });
            });
        })();
        </script>
        @endpush
    @endif

    @push('scripts')
    <script>
        (function () {
            var cb = document.getElementById('g_create_login'), box = document.getElementById('g-login-fields');
            if (!cb || !box) return;
            function sync() { box.style.display = cb.checked ? '' : 'none'; }
            cb.addEventListener('change', sync); sync();
        })();
    </script>
    @endpush
</x-app-layout>
