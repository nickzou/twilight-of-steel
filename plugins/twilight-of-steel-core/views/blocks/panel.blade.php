{{-- Panel block (src/blocks/panel/render.php). The wrapper attributes, the
     <img>, the outline SVG and the bubble text come pre-escaped or sanitised. --}}
<figure {!! $wrapper !!}>
  {!! $image !!}
  @if ($bubbles)
    {{-- The outlines are drawing only; the text below is what's read out. --}}
    <div class="twilight-bubbles__outlines" aria-hidden="true">{!! $outlines !!}</div>
    <div class="twilight-bubbles">
      @foreach ($bubbles as $bubble)
        <p class="{{ $bubble['class'] }}" style="{{ $bubble['style'] }}">{!! $bubble['text'] !!}</p>
      @endforeach
    </div>
  @endif
</figure>
