@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::before(Auth::user()->name, ' ') ?: Auth::user()->name;
@endphp
<x-app-layout>
    <div class="greeting">
        <div class="date">{{ now()->format('l, j F Y') }}</div>
        <h1>{{ $greeting }}, {{ $firstName }}.</h1>
        <p>
            @if(! $school)
                You're not linked to a school yet. Ask an administrator to add you to one.
            @else
                @switch($panel)
                    @case('admin') Here's where {{ $school->name }} stands today. @break
                    @case('teacher') Here's your teaching day at {{ $school->name }}. @break
                    @case('student') Here's what's happening in your class. @break
                    @case('parent') Here's how your {{ \Illuminate\Support\Str::plural('child', ($children ?? collect())->count()) }} {{ ($children ?? collect())->count() === 1 ? 'is' : 'are' }} doing. @break
                    @default Welcome back.
                @endswitch
            @endif
        </p>
    </div>

    @if($school)
        @include('dashboard.' . $panel)
    @endif
</x-app-layout>
