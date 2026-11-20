@php
    // Friendly label + hint for each permission name
    $labels = [
        'students.view' => ['View students', 'See the student register'],
        'students.create' => ['Add students', 'Admit new students & import'],
        'students.update' => ['Edit students', 'Change student details'],
        'students.delete' => ['Delete students', 'Remove student records'],
        'attendance.view' => ['View attendance', ''],
        'attendance.create' => ['Take attendance', 'Mark daily attendance'],
        'attendance.update' => ['Edit attendance', ''],
        'attendance.manage' => ['Manage attendance', 'Full attendance control'],
        'attendance.view_reports' => ['Attendance reports', 'Daily / monthly / per-student'],
        'timetable.view' => ['View timetable', ''],
        'timetable.manage' => ['Manage timetable', 'Build class timetables'],
        'homework.view' => ['View homework', ''],
        'homework.manage' => ['Manage homework', 'Set & evaluate homework'],
        'homework.submit' => ['Submit homework', 'For students'],
        'fees.view' => ['View fees', ''],
        'fees.create' => ['Create fees', ''],
        'fees.update' => ['Edit fees', ''],
        'fees.collect' => ['Collect fees', ''],
        'fees.refund' => ['Refund fees', ''],
        'exams.view' => ['View exams', ''],
        'exams.create' => ['Create exams', ''],
        'exams.update' => ['Edit exams', ''],
        'marks.enter' => ['Enter marks', ''],
        'results.publish' => ['Publish results', ''],
        'academic.manage' => ['Academic setup', 'Sessions, classes, sections, subjects'],
        'staff.manage' => ['Manage staff & teachers', ''],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Roles &amp; permissions</span>
                    <h1 class="h3 mb-1">{{ $role->name }}</h1>
                    <p>Tick what this role may do. Everyone with the <strong>{{ $role->name }}</strong> role is affected.</p>
                </div>
                <div>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" id="roles-form">
        @csrf
        @method('PUT')

        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-light" data-bulk="all"><i class="bi bi-check2-all"></i> Select all</button>
            <button type="button" class="btn btn-sm btn-light" data-bulk="none"><i class="bi bi-x-lg"></i> Clear all</button>
        </div>

        <div class="row g-4">
            @foreach($groups as $heading => $permissions)
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="panel-body p-3 p-lg-4">
                            <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h6 mb-0">{{ $heading }}</h2>
                                <button type="button" class="btn btn-sm btn-link p-0" data-group-toggle>Toggle</button>
                            </div>
                            @foreach($permissions as $permission)
                                @php [$label, $hint] = $labels[$permission->name] ?? [\Illuminate\Support\Str::headline($permission->name), '']; @endphp
                                <div class="form-check d-flex align-items-start gap-2 mb-2">
                                    <input class="form-check-input mt-1 perm-box" type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                           id="perm-{{ $permission->id }}" @checked($assigned->has($permission->name))>
                                    <label class="form-check-label" for="perm-{{ $permission->id }}">
                                        {{ $label }}
                                        <code class="small text-muted d-block">{{ $permission->name }}</code>
                                        @if($hint)<span class="small text-muted">{{ $hint }}</span>@endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save permissions</button>
        </div>
    </form>

    @push('scripts')
    <script>
        var form = document.getElementById('roles-form');
        form.querySelectorAll('[data-bulk]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var on = btn.getAttribute('data-bulk') === 'all';
                form.querySelectorAll('.perm-box').forEach(function (b) { b.checked = on; });
            });
        });
        form.querySelectorAll('[data-group-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var boxes = btn.closest('.panel-body').querySelectorAll('.perm-box');
                var allOn = Array.prototype.every.call(boxes, function (b) { return b.checked; });
                boxes.forEach(function (b) { b.checked = !allOn; });
            });
        });
    </script>
    @endpush
</x-app-layout>
