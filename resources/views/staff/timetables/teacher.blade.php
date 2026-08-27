<x-app-layout>
    <x-slot name="header">
        <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __('My Timetable') }}</h1></div></div></div>
    </x-slot>

    <div class="panel">
        <div class="panel-body p-3 p-lg-4">
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead>
                        <tr>
                            <th style="width: 15%">Day</th>
                            <th>Periods</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($days as $num => $dayName)
                            <tr>
                                <td class="fw-bold">{{ $dayName }}</td>
                                <td class="text-start">
                                    @if($timetables->has($num))
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($timetables[$num] as $period)
                                                <div class="card border border-success shadow-sm" style="min-width: 150px; background-color: var(--bs-body-bg);">
                                                    <div class="card-body p-2">
                                                        <small class="fw-bold text-success d-block mb-1">{{ \Carbon\Carbon::parse($period->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($period->end_time)->format('h:i A') }}</small>
                                                        <div class="fw-semibold">{{ $period->subject->name }}</div>
                                                        <small class="d-block text-muted"><i class="bi bi-people"></i> {{ $period->schoolClass->name }} ({{ $period->section->name }})</small>
                                                        @if($period->room_number)
                                                            <small class="d-block text-muted"><i class="bi bi-geo-alt"></i> Room: {{ $period->room_number }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted fst-italic">No periods scheduled</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
