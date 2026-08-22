<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('Dashboard') }}</h1></div></div></div>
    </x-slot>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="metric-card metric-primary h-100">
                <div class="panel-body p-3 p-lg-4">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                Total Students</div>
                            <div class="h5 mb-0 font-weight-bold">1,250</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-white-50" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="metric-card metric-success h-100">
                <div class="panel-body p-3 p-lg-4">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">
                                Active Teachers</div>
                            <div class="h5 mb-0 font-weight-bold">45</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-badge fa-2x text-white-50" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body p-4">
            <h5 class="card-title">Welcome to {{ optional(Auth::user()->currentSchool)->name ?? config('app.name') }}</h5>
            <p class="card-text text-muted">You are logged in as <strong>{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</strong>.</p>
        </div>
    </div>
</x-app-layout>
