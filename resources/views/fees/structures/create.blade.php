<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Class-wise fees</span><h1 class="h3 mb-1">Add class fee</h1></div>
            <a href="{{ route('fees.structures.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
        </div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 640px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ route('fees.structures.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Session <span class="text-danger">*</span></label>
                        <select name="academic_session_id" class="form-select @error('academic_session_id') is-invalid @enderror" required>
                            @foreach($sessions as $s)<option value="{{ $s->id }}" @selected(old('academic_session_id', optional($sessions->firstWhere('is_current', true))->id) == $s->id)>{{ $s->name }}</option>@endforeach
                        </select>
                        @error('academic_session_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Class <span class="text-danger">*</span></label>
                        <select name="school_class_id" class="form-select @error('school_class_id') is-invalid @enderror" required>
                            <option value="">Select class</option>
                            @foreach($classes as $c)<option value="{{ $c->id }}" @selected(old('school_class_id') == $c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                        @error('school_class_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fee title <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title', 'Tuition Fee') }}" class="form-control @error('title') is-invalid @enderror" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                            @foreach(\App\Models\FeeStructure::FREQUENCIES as $k => $v)<option value="{{ $k }}" @selected(old('frequency', 'monthly') === $k)>{{ $v }}</option>@endforeach
                        </select>
                        @error('frequency') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-text">Amount is charged <strong>each period</strong>. Monthly = 12 installments, Quarterly = 4, Yearly = 1 (based on the session's months).</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                    <a href="{{ route('fees.structures.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
