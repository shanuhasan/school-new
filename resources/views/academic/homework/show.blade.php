<x-app-layout>

<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Homework Details') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Homework Details</h4>
        <div>
            <a href="{{ route('academic.homework.index') }}" class="btn btn-secondary btn-sm">Back</a>
            @can('update', $homework)
                <a href="{{ route('academic.homework.edit', $homework) }}" class="btn btn-primary btn-sm ms-2"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel mb-4">
                <div class="panel-body p-4">
                    <h5 class="fw-bold mb-4">Assignment Info</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <span class="text-muted d-block small">Subject</span>
                            <span class="fw-semibold fs-5">{{ $homework->subject->name }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted d-block small">Class & Section</span>
                            <span class="fw-semibold">{{ $homework->schoolClass->name }} ({{ $homework->section->name ?? 'All sections' }})</span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted d-block small">Teacher</span>
                            <span class="fw-semibold">{{ $homework->teacher->name }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted d-block small">Date Assigned</span>
                            <span class="fw-semibold">{{ $homework->homework_date->format('d M, Y') }}</span>
                        </li>
                        <li class="mb-3">
                            <span class="text-muted d-block small">Due Date</span>
                            <span class="fw-semibold text-danger">{{ $homework->due_date->format('d M, Y') }}</span>
                        </li>
                        @if($homework->attachment_path)
                            <li class="mt-4">
                                <a href="{{ route('academic.homework.download', $homework) }}" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-download"></i> Download Attachment
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
            
            @if(auth()->user()->hasRole('Student'))
                <!-- Student Submission Form -->
                @php
                    $mySubmission = $homework->submissions->where('student_id', auth()->user()->studentProfile?->id ?? 0)->first();
                @endphp
                <div class="panel border-primary border-2">
                    <div class="panel-body p-4">
                        <h5 class="fw-bold text-primary mb-3">My Submission</h5>
                        
                        @if($mySubmission)
                            <div class="mb-3">
                                <span class="badge bg-{{ $mySubmission->status == 'evaluated' ? 'success' : 'warning' }} mb-2">{{ ucfirst($mySubmission->status) }}</span>
                                <div class="text-muted small">Submitted on {{ $mySubmission->submitted_at->format('d M, Y h:i A') }}</div>
                            </div>
                            
                            @if($mySubmission->status == 'evaluated')
                                <div class="alert alert-success p-3">
                                    <h6 class="fw-bold mb-1">Marks: {{ $mySubmission->marks ?? 'N/A' }}/100</h6>
                                    @if($mySubmission->teacher_feedback)
                                        <p class="mb-0 mt-2 small"><strong>Feedback:</strong> {{ $mySubmission->teacher_feedback }}</p>
                                    @endif
                                </div>
                            @endif
                        @endif
                        
                        @if(!$mySubmission || $mySubmission->status != 'evaluated')
                            <form action="{{ route('academic.homework.submissions.store', $homework) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Your Answer (Optional)</label>
                                    <textarea name="submission_text" class="form-control" rows="4">{{ $mySubmission->submission_text ?? '' }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Attach File</label>
                                    <input type="file" name="attachment" class="form-control">
                                    @if($mySubmission && $mySubmission->attachment_path)
                                        <div class="form-text text-info mt-1">You already uploaded a file. Uploading a new one will replace it.</div>
                                    @endif
                                </div>
                                <button class="btn btn-primary w-100">{{ $mySubmission ? 'Update Submission' : 'Submit Homework' }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        <div class="col-lg-8">
            <div class="panel mb-4">
                <div class="panel-body p-4">
                    <h3 class="fw-bold">{{ $homework->title }}</h3>
                    <hr>
                    <div class="mt-4" style="white-space: pre-wrap;">{{ $homework->description }}</div>
                </div>
            </div>
            
            {{-- Teacher who set this homework, or staff with homework.manage ("manage" is not a HomeworkPolicy ability) --}}
            @can('update', $homework)
                <div class="panel">
                    <div class="panel-body p-4">
                        <h5 class="fw-bold mb-4">Student Submissions</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Submitted At</th>
                                        <th>Marks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($homework->submissions as $sub)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                        {{ mb_substr($sub->student?->first_name ?? '?', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <span class="fw-semibold d-block">{{ $sub->student?->full_name ?? 'Unknown student' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $sub->status == 'evaluated' ? 'success' : 'warning' }}">{{ ucfirst($sub->status) }}</span>
                                            </td>
                                            <td>{{ $sub->submitted_at ? $sub->submitted_at->format('d M, h:i A') : '-' }}</td>
                                            <td><span class="fw-bold text-success">{{ $sub->marks ?? '-' }}</span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#evaluateModal{{ $sub->id }}">
                                                    Review
                                                </button>
                                                
                                                <!-- Evaluate Modal -->
                                                <div class="modal fade" id="evaluateModal{{ $sub->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header border-bottom-0">
                                                                <h5 class="modal-title fw-bold">Evaluate Submission</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="{{ route('academic.homework.submissions.evaluate', $sub) }}" method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <div class="modal-body py-0">
                                                                    <div class="mb-3 p-3 bg-light rounded text-dark">
                                                                        <div class="mb-2"><strong>Student:</strong> {{ $sub->student?->full_name ?? 'Unknown student' }}</div>
                                                                        <div class="mb-2"><strong>Answer:</strong> <br>{{ $sub->submission_text ?: 'No text provided.' }}</div>
                                                                        @if($sub->attachment_path)
                                                                            <div>
                                                                                <strong>File:</strong> 
                                                                                <a href="{{ route('academic.homework.submissions.download', $sub) }}" class="ms-2 btn btn-xs btn-primary">Download</a>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">Marks (out of 100)</label>
                                                                            <input type="number" name="marks" class="form-control" value="{{ $sub->marks }}" min="0" max="100">
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Feedback</label>
                                                                        <textarea name="teacher_feedback" class="form-control" rows="3">{{ $sub->teacher_feedback }}</textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer border-top-0">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-success">Save Evaluation</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted fst-italic">No submissions yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>
</x-app-layout>
