@php
    $report = session('import_report');
    $currentSession = $sessions->firstWhere('is_current', true) ?? $sessions->first();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Students</span>
                    <h1 class="h3 mb-1">Import students</h1>
                    <p>Add many students at once from an Excel or CSV file.</p>
                </div>
                <div>
                    <a href="{{ route('students.import.template') }}" class="btn btn-light"><i class="bi bi-download"></i> Download template</a>
                    <a href="{{ route('students.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to students</a>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- ===== Result of the last upload ===== --}}
    @if($report)
        @if($report['imported'])
            @php $s = $report['imported']; @endphp
            <div class="alert alert-success">
                <div>
                    <strong>{{ $s['students'] }} {{ \Illuminate\Support\Str::plural('student', $s['students']) }} imported</strong> from {{ $report['file'] }}.
                    @if($s['enrolled']) {{ $s['enrolled'] }} enrolled in a class. @endif
                    @if($s['guardians_created']) {{ $s['guardians_created'] }} new {{ \Illuminate\Support\Str::plural('guardian', $s['guardians_created']) }} added. @endif
                    @if($s['guardians_linked']) {{ $s['guardians_linked'] }} linked to existing guardians. @endif
                    @if($report['error_count']) <br>{{ $report['error_count'] }} {{ \Illuminate\Support\Str::plural('row', $report['error_count']) }} skipped because of errors (listed below). @endif
                    <a href="{{ route('students.index') }}" class="fw-semibold">View students →</a>
                </div>
            </div>
        @else
            <div class="alert alert-danger">
                <div>
                    <strong>Nothing was imported.</strong>
                    {{ $report['error_count'] }} of {{ $report['total'] }} {{ \Illuminate\Support\Str::plural('row', $report['total']) }} in {{ $report['file'] }} need fixing.
                    @if($report['valid_count'] > 0)
                        Fix them and upload again, or tick <em>“Import valid rows and skip the rest”</em>.
                    @else
                        Fix them and upload the file again.
                    @endif
                </div>
            </div>
        @endif

        @if(! empty($report['errors']))
            <div class="panel mb-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>Rows with problems</h2>
                        <span class="text-muted small">Row numbers match your spreadsheet (row 1 is the header).</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th style="width:90px">Row</th><th>What to fix</th></tr></thead>
                            <tbody>
                                @foreach($report['errors'] as $line => $messages)
                                    <tr>
                                        <td class="fw-semibold">{{ $line }}</td>
                                        <td class="text-wrap">
                                            @foreach($messages as $m)<div>{{ $m }}</div>@endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($report['error_count'] > count($report['errors']))
                        <p class="small text-muted mt-3 mb-0">Showing the first {{ count($report['errors']) }} of {{ $report['error_count'] }} problem rows.</p>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <div class="row g-4">
        {{-- ===== Upload ===== --}}
        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Upload file</h2></div>

                    <ol class="small text-muted ps-3 mb-4">
                        <li><a href="{{ route('students.import.template') }}">Download the template</a> and open it in Excel.</li>
                        <li>Fill one student per row. Keep the header row as it is.</li>
                        <li>Save it as <strong>.xlsx</strong> or <strong>.csv</strong> and upload it here.</li>
                    </ol>

                    <form action="{{ route('students.import.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="import-file">Student file <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="import-file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                   class="form-control @error('file') is-invalid @enderror" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">.xlsx or .csv, up to 5 MB and {{ number_format($maxRows) }} students.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="import-session">Enrol into session</label>
                            <select name="academic_session_id" id="import-session" class="form-select @error('academic_session_id') is-invalid @enderror">
                                <option value="">Don't enrol (ignore class column)</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}" @selected(old('academic_session_id', $currentSession?->id) == $session->id)>
                                        {{ $session->name }}{{ $session->is_current ? ' (current)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Rows with a <code>class</code> value are enrolled in this session.</div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="skip_invalid" value="1" id="skip-invalid" @checked(old('skip_invalid'))>
                            <label class="form-check-label" for="skip-invalid">Import valid rows and skip the rest</label>
                            <div class="form-text mt-0">Leave unticked to import only when the whole file is correct.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="import-submit">
                            <i class="bi bi-upload"></i> Import students
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Format guide ===== --}}
        <div class="col-lg-7">
            <div class="panel mb-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>File format</h2>
                        <span class="small text-muted"><span class="text-danger">*</span> required</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <thead><tr><th>Column</th><th>Notes</th></tr></thead>
                            <tbody>
                                @foreach($columns as $key => [$label, $required, $help])
                                    <tr>
                                        <td><code>{{ $label }}</code>@if($required)<span class="text-danger"> *</span>@endif</td>
                                        <td class="text-wrap small text-muted">{{ $help ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3">
                        <h2>Class and section names in this school</h2>
                        <p class="small">Type them exactly like this in the <code>class</code> and <code>section</code> columns (capital letters don't matter).</p>
                    </div>
                    @if($classes->isEmpty())
                        <div class="empty-note">
                            <i class="bi bi-door-open" aria-hidden="true"></i>
                            No classes yet. <a href="{{ route('academic.classes.create') }}">Create classes</a> first if you want to enrol students during import.
                        </div>
                    @else
                        <div class="d-flex flex-column gap-2">
                            @foreach($classes as $class)
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="fw-semibold" style="min-width:110px">{{ $class->name }}</span>
                                    @forelse($class->sections as $section)
                                        <span class="badge bg-secondary">{{ $section->name }}</span>
                                    @empty
                                        <span class="small text-muted">no sections</span>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Prevent double submits on large files
        document.querySelector('form[action$="students/import"]')?.addEventListener('submit', function () {
            var btn = document.getElementById('import-submit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importing…';
        });
    </script>
    @endpush
</x-app-layout>
