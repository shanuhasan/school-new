@if($items->isNotEmpty())
    <ul class="feed">
        @foreach($items as $hw)
            <li>
                <div class="feed-date">
                    <b>{{ $hw->due_date?->format('j') }}</b>
                    <small>{{ $hw->due_date?->format('M') }}</small>
                </div>
                <div class="feed-body">
                    <strong><a href="{{ route('academic.homework.show', $hw) }}" class="text-body">{{ $hw->title }}</a></strong>
                    <span>
                        {{ $hw->subject->name ?? '' }}
                        @if($hw->schoolClass ?? false) · {{ $hw->schoolClass->name }}{{ $hw->section ? ' ' . $hw->section->name : '' }} @endif
                    </span>
                </div>
            </li>
        @endforeach
    </ul>
@else
    <div class="empty-note"><i class="bi bi-journal-check" aria-hidden="true"></i>Nothing due. All caught up.</div>
@endif
