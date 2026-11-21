<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Fees</span><h1 class="h3 mb-1">New fee</h1></div>
            <a href="{{ route('fees.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
        </div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 720px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ route('fees.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                            <option value="">Select student</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $s->full_name }} — {{ $s->admission_number }}</option>
                            @endforeach
                        </select>
                        @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Session</label>
                        <select name="academic_session_id" class="form-select">
                            <option value="">—</option>
                            @foreach($sessions as $ses)
                                <option value="{{ $ses->id }}" @selected(old('academic_session_id', optional($sessions->firstWhere('is_current', true))->id) == $ses->id)>{{ $ses->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Fee title <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. Tuition Fee — November" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Discount</label>
                        <input type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fine</label>
                        <input type="number" step="0.01" min="0" name="fine" value="{{ old('fine', 0) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Due date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                    <a href="{{ route('fees.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary">Create fee</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
