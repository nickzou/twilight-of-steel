{{-- Timeline block. inc/timeline.php prepares $events; description is HTML that's already been sanitised. --}}
<ol class="{{ $class }}">
  @foreach ($events as $event)
    <li class="timeline-event">
      @if ($event['date'])
        <p class="timeline-date">{{ $event['date'] }}</p>
      @endif
      <h3 class="timeline-title">
        @if ($event['url'])
          <a href="{{ $event['url'] }}">{{ $event['title'] }}</a>
        @else
          {{ $event['title'] }}
        @endif
      </h3>
      @if ($event['description'])
        <div class="timeline-description">{!! $event['description'] !!}</div>
      @endif
    </li>
  @endforeach
</ol>
