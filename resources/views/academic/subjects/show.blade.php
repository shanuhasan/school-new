@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
    $classIds = $subject->classes->pluck('id');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div>
                    <span class="eyebrow">Subject{{ $subject->code ? ' · ' . $subject->code : '' }}</span>
                    <h1 class="h3 mb-1">{{ $subject->name }}</h1>
                    <p>Taught in {{ $subject->classes->count() }} {{ \Illuminate\Support\Str::plural('class', $subject->classes->count()) }} by {{ $teachers->count() }} {{ \Illuminate\Support\Str::plural('teacher', $teachers->count()) }}.</p>
                </div>
                <div>
                    <a href="{{ route('academic.subjects.edit', $subject) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="{{ route('academic.subjects.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to subjects</a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-6">
            {{-- Classes --}}
            <div class="panel mb-4">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Classes</h2></div>
                    @if($subject->classes->isEmpty())
                        <div class="empty-note">
                            <i class="bi bi-door-open" aria-hidden="true"></i>
                            Not part of any class yet. Add it from a class's edit page.
                        </div>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($subject->classes as $class)
                                <li class="d-flex align-items-center gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <a href="{{ route('academic.classes.show', $class) }}" class="fw-semibold text-body me-auto">{{ $class->name }}</a>
                                    <span class="badge {{ $class->pivot->type === 'optional' ? 'bg-secondary' : 'bg-primary' }}">{{ ucfirst($class->pivot->type ?? 'compulsory') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Teachers --}}
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header mb-3"><h2>Teachers</h2></div>
                    @if($teachers->isEmpty())
                        <div class="empty-note"><i class="bi bi-person-badge" aria-hidden="true"></i>No teacher teaches this subject yet. Set subjects on a teacher's profile.</div>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach($teachers as $teacher)
                                @php $teachesHere = $teacher->classes->whereIn('id', $classIds); @endphp
                                <li class="d-flex align-items-center gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <span class="initials">{{ $initials($teacher->user?->name) }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('staff.teachers.show', $teacher) }}" class="fw-semibold text-body">{{ $teacher->user?->name }}</a>
                                        <div class="small text-muted text-truncate">
                                            {{ $teachesHere->isNotEmpty() ? 'Classes: ' . $teachesHere->pluck('name')->implode(', ') : 'No matching class assigned' }}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Homework --}}
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-body p-3 p-lg-4">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-3">
                        <h2>Recent homework</h2>
                        <a href="{{ route('academic.homework.create') }}" class="small fw-medium">Set homework</a>
                    </div>
                    @if($homework->isEmpty())
                        <div class="empty-note"><i class="bi bi-journal" aria-hidden="true"></i>No homework set for this subject yet.</div>
                    @else
                        <ul class="feed">
                            @foreach($homework as $hw)
                                <li>
                                    <div class="feed-date">
                                        <b>{{ $hw->due_date?->format('j') }}</b>
                                        <small>{{ $hw->due_date?->format('M') }}</small>
                                    </div>
                                    <div class="feed-body">
                                        <strong><a href="{{ route('academic.homework.show', $hw) }}" class="text-body">{{ $hw->title }}</a></strong>
                                        <span>
                                            {{ $hw->schoolClass->name ?? '' }}{{ $hw->section ? ' ' . $hw->section->name : '' }}
                                            · due {{ $hw->due_date?->isPast() && ! $hw->due_date?->isToday() ? $hw->due_date->diffForHumans() : $hw->due_date?->format('D, j M') }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
