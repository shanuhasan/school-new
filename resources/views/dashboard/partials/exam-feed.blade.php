@if($exams->isNotEmpty())
    <ul class="feed">
        @foreach($exams as $exam)
            <li>
                <div class="feed-date">
                    <b>{{ $exam->start_date?->format('j') }}</b>
                    <small>{{ $exam->start_date?->format('M') }}</small>
                </div>
                <div class="feed-body">
                    <strong><a href="{{ route('academic.exams.show', $exam) }}" class="text-body">{{ $exam->name }}</a></strong>
                    <span>
                        {{ $exam->examType->name ?? 'Exam' }}
                        @if($exam->start_date && $exam->end_date && ! $exam->start_date->isSameDay($exam->end_date))
                            · until {{ $exam->end_date->format('j M') }}
                        @endif
                    </span>
                </div>
            </li>
        @endforeach
    </ul>
@else
    <div class="empty-note"><i class="bi bi-calendar-x" aria-hidden="true"></i>No exams scheduled.</div>
@endif
