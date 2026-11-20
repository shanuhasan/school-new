@php
    use Illuminate\Support\Str;
    $report = session('import_report');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Guardians</span>
                    <h1 class="h3 mb-1">Import guardians</h1>
                    <p>Add parents in bulk and link them to their children by admission number.</p>
                </div>
                <div>
                    <a href="{{ route('guardians.import.template') }}" class="btn btn-light"><i class="bi bi-download"></i> Download template</a>
                    <a href="{{ route('guardians.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to guardians</a>
                </div>
            </div>
        </div>
    </x-slot>

    @if($report)
        @if($report['imported'])
            @php $s = $report['imported']; @endphp
            <div class="alert alert-success">
                <div>
                    <strong>Import finished</strong> for {{ $report['file'] }}:
                    {{ $s['created'] }} new {{ Str::plural('guardian', $s['created']) }},
                    {{ $s['existing'] }} already on file (matched by phone),
                    {{ $s['links'] }} new {{ Str::plural('link', $s['links']) }} to students.
                    @if($report['error_count']) <br>{{ $report['error_count'] }} {{ Str::plural('row', $report['error_count']) }} skipped because of errors (listed below). @endif
                    <a href="{{ route('guardians.index') }}" class="fw-semibold">View guardians →</a>
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
                        <li><a href="{{ route('guardians.import.template') }}">Download the template</a> and open it in Excel.</li>
                        <li>Fill one guardian per row. To link a parent to several children, list their admission numbers separated by commas.</li>
                        <li>Save it as <strong>.xlsx</strong> or <strong>.csv</strong> and upload it here.</li>
                    </ol>

                    <form action="{{ route('guardians.import.store') }}" method="POST" enctype="multipart/form-data" id="guardian-import-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="import-file">Guardian file <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="import-file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                   class="form-control @error('file') is-invalid @enderror" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">.xlsx or .csv, up to 5 MB and {{ number_format($maxRows) }} rows.</div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="skip_invalid" value="1" id="skip-invalid" @checked(old('skip_invalid'))>
                            <label class="form-check-label" for="skip-invalid">Import valid rows and skip the rest</label>
                            <div class="form-text mt-0">Leave unticked to import only when the whole file is correct.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="import-submit">
                            <i class="bi bi-upload"></i> Import guardians
                        </button>
                    </form>

                    <hr class="my-4">
                    <h3 class="h6 fw-semibold mb-2">Good to know</h3>
                    <ul class="small text-muted ps-3 mb-0">
                        <li>The <strong>phone number</strong> identifies a guardian. If it is already on file, that guardian is reused and only the new student links are added. Their saved details are not changed.</li>
                        <li>Students must already exist. Import students first, then guardians.</li>
                        <li>An exported guardians file can be uploaded again as it is.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>File format</h2>
                        <span class="small text-muted"><span class="text-danger">*</span> required</span>
                    </div>
                    <div class="table-responsive mb-4">
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

                    <h3 class="h6 fw-semibold mb-2">Example</h3>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0 small">
                            <thead><tr><th>first_name</th><th>last_name</th><th>phone</th><th>student_admission_number</th><th>relationship</th><th>emergency_contact</th></tr></thead>
                            <tbody>
                                <tr><td>Anil</td><td>Sharma</td><td>9876500001</td><td>ADM-101, ADM-102</td><td>Father</td><td>yes</td></tr>
                                <tr><td>Sunita</td><td>Sharma</td><td>9876500002</td><td>ADM-101</td><td>Mother</td><td>no</td></tr>
                                <tr><td>Rahul</td><td>Verma</td><td>9876500003</td><td></td><td></td><td></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('guardian-import-form').addEventListener('submit', function () {
            var btn = document.getElementById('import-submit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Importing…';
        });
    </script>
    @endpush
</x-app-layout>
