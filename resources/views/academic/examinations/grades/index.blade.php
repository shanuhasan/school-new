<x-app-layout>
<x-slot name="header">
    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Grades Setup') }}</h1></div></div></div>
</x-slot>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="panel">
                <div class="panel-body p-4">
                    <h5 class="mb-3">Add Grade</h5>
                    <form action="{{ route('academic.grades.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name (e.g., A, B, A+)</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Min %</label>
                                <input type="number" step="0.01" name="min_percentage" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Max %</label>
                                <input type="number" step="0.01" name="max_percentage" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="panel">
                <div class="panel-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Min %</th>
                                    <th>Max %</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grades as $grade)
                                    <tr>
                                        <td><strong>{{ $grade->name }}</strong></td>
                                        <td>{{ $grade->min_percentage }}%</td>
                                        <td>{{ $grade->max_percentage }}%</td>
                                        <td class="text-end">
                                            <form action="{{ route('academic.grades.destroy', $grade) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
