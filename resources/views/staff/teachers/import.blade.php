@php
    use Illuminate\Support\Str;
    $report = session('import_report');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Staff &amp; Teachers</span>
                    <h1 class="h3 mb-1">Import teachers</h1>
                    <p>Add teaching staff in bulk. Each teacher gets their own sign-in account.</p>
                </div>
                <div>
                    <a href="{{ route('staff.teachers.import.template') }}" class="btn btn-light"><i class="bi bi-download"></i> Download template</a>
                    <a href="{{ route('staff.teachers.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to teachers</a>
                </div>
            </div>
        </div>
    </x-slot>

    @if($report)
        @if($report['imported'])
            @php $s = $report['imported']; @endphp
            <div class="alert alert-success">
                <div>
                    <strong>{{ $s['teachers'] }} {{ Str::plural('teacher', $s['teachers']) }} imported</strong> from {{ $report['file'] }}.
                    @if($s['departments_created']) {{ $s['departments_created'] }} new {{ Str::plural('department', $s['departments_created']) }} created. @endif
                    @if($s['designations_created']) {{ $s['designations_created'] }} new {{ Str::plural('designation', $s['designations_created']) }} created. @endif
                    @if($report['error_count']) <br>{{ $report['error_count'] }} {{ Str::plural('row', $report['error_count']) }} skipped because of errors (listed below). @endif
                    <br>Teachers can set their password with <em>Forgot password</em> on the sign-in page, using their email.
                    <a href="{{ route('staff.teachers.index') }}" class="fw-semibold">View teachers →</a>
                </div>
            </div>
        @else
            <div class="alert alert-danger">
                <div>
                    <strong>Nothing was imported.</strong>
                    {{ $report['error_count'] }} of {{ $report['total'] }} {{ Str::plural('row', $report['total']) }} in {{ $report['file'] }} need fixing.
                    @if($report['valid_count'] > 0)
                        Fix them and upload again, or tick <em>“Import valid rows and skip the rest”</em>.
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
                                        <td class="text-wrap">@foreach($messages as $m)<div>{{ $m }}</div>@endforeach</td>
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
        <div class="col-lg-5">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Upload file</h2></div>

                    <ol class="small text-muted ps-3 mb-4">
                        <li><a href="{{ route('staff.teachers.import.template') }}">Download the template</a> and open it in Excel.</li>
                        <li>Fill one teacher per row. Keep the header row as it is.</li>
                        <li>Save it as <strong>.xlsx</strong> or <strong>.csv</strong> and upload it here.</li>
                    </ol>

                    <form action="{{ route('staff.teachers.import.store') }}" method="POST" enctype="multipart/form-data" id="teacher-import-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="import-file">Teacher file <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="import-file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                   class="form-control @error('file') is-invalid @enderror" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">.xlsx or .csv, up to 5 MB and {{ number_format($maxRows) }} teachers.</div>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="create_missing" value="1" id="create-missing" @checked(old('create_missing'))>
                            <label class="form-check-label" for="create-missing">Create missing departments and designations</label>
                            <div class="form-text mt-0">Otherwise, unknown names are reported as errors.</div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="skip_invalid" value="1" id="skip-invalid" @checked(old('skip_invalid'))>
                            <label class="form-check-label" for="skip-invalid">Import valid rows and skip the rest</label>
                            <div class="form-text mt-0">Leave unticked to import only when the whole file is correct.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="import-submit">
                            <i class="bi bi-upload"></i> Import teachers
                        </button>
                    </form>

                    <hr class="my-4">
                    <h3 class="h6 fw-semibold mb-2">Good to know</h3>
                    <ul class="small text-muted ps-3 mb-0">
                        <li>Each teacher gets a <strong>sign-in account</strong> with the Teacher role. No password is sent; they choose one with <em>Forgot password</em> on the sign-in page.</li>
                        <li>An <strong>email</strong> can only belong to one account, so emails already in use are reported.</li>
                        <li>Subjects and classes must already exist. Write several separated by commas.</li>
                    </ul>
                </div>
            </div>
        </div>

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
                                @foreach($columns as $key => [$required, $help])
                                    <tr>
                                        <td><code>{{ $key }}</code>@if($required)<span class="text-danger"> *</span>@endif</td>
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
                        <h2>Names in this school</h2>
                        <p class="small">Use these exactly (capital letters don't matter).</p>
                    </div>
                    @foreach([
                        'Departments' => $departments,
                        'Designations' => $designations,
                        'Subjects' => $subjects->map(fn ($s) => $s->code ? "{$s->name} ({$s->code})" : $s->name),
                        'Classes' => $classes,
                    ] as $label => $names)
                        <div class="mb-3">
                            <div class="small fw-semibold mb-1">{{ $label }}</div>
                            <div class="d-flex flex-wrap gap-1">
                                @forelse($names as $n)
                                    <span class="badge bg-secondary">{{ $n }}</span>
                                @empty
                                    <span class="small text-muted">None yet</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('teacher-import-form').addEventListener('submit', function () {
            var btn = document.getElementById('import-submit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importing…';
        });
    </script>
    @endpush
</x-app-layout>
