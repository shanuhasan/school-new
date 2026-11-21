<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy">
            <div><span class="eyebrow">Salaries</span><h1 class="h3 mb-1">New payslip</h1></div>
            <a href="{{ route('salaries.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
        </div></div>
    </x-slot>

    <div class="panel mx-auto" style="max-width: 720px;">
        <div class="panel-body p-3 p-lg-4">
            <form method="POST" action="{{ route('salaries.store') }}">
                @csrf
                <input type="hidden" name="user_id" id="emp_user_id" value="{{ old('user_id') }}">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        @if($employees->isEmpty())
                            <input type="text" name="employee_name" value="{{ old('employee_name') }}" class="form-control @error('employee_name') is-invalid @enderror" placeholder="Employee name" required>
                            <div class="form-text">No teachers/staff found — type a name.</div>
                        @else
                            <select id="emp_select" class="form-select" onchange="pickEmp(this)">
                                <option value="">Select employee</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e['user_id'] }}" data-name="{{ $e['name'] }}" data-designation="{{ $e['designation'] }}"
                                        @selected(old('user_id') == $e['user_id'])>{{ $e['name'] }} — {{ $e['designation'] }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="employee_name" id="emp_name" value="{{ old('employee_name') }}">
                        @endif
                        @error('employee_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Month <span class="text-danger">*</span></label>
                        <input type="month" name="month" value="{{ old('month', $month) }}" class="form-control @error('month') is-invalid @enderror" required>
                        @error('month') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <input type="hidden" name="designation" id="emp_designation" value="{{ old('designation') }}">

                    <div class="col-md-4">
                        <label class="form-label">Basic <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="basic" value="{{ old('basic') }}" class="form-control @error('basic') is-invalid @enderror js-amt" required>
                        @error('basic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Allowances</label>
                        <input type="number" step="0.01" min="0" name="allowances" value="{{ old('allowances', 0) }}" class="form-control js-amt">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Deductions</label>
                        <input type="number" step="0.01" min="0" name="deductions" value="{{ old('deductions', 0) }}" class="form-control js-amt">
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info d-flex justify-content-between mb-0">
                            <span>Net salary (basic + allowances − deductions)</span>
                            <strong id="net_preview">0.00</strong>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3 mt-3">
                    <a href="{{ route('salaries.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary">Create payslip</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function pickEmp(sel) {
            var o = sel.options[sel.selectedIndex];
            document.getElementById('emp_user_id').value = o.value || '';
            document.getElementById('emp_name').value = o.getAttribute('data-name') || '';
            document.getElementById('emp_designation').value = o.getAttribute('data-designation') || '';
        }
        (function () {
            var net = document.getElementById('net_preview');
            function calc() {
                var v = function (n) { return parseFloat(document.querySelector('[name="' + n + '"]').value) || 0; };
                net.textContent = (v('basic') + v('allowances') - v('deductions')).toFixed(2);
            }
            document.querySelectorAll('.js-amt').forEach(function (el) { el.addEventListener('input', calc); });
            var s = document.getElementById('emp_select'); if (s && s.value) pickEmp(s);
            calc();
        })();
    </script>
    @endpush
</x-app-layout>
