@if($paginator->hasPages())
<div class="pager">
  @if($paginator->onFirstPage())
  <span style="opacity:.3;cursor:default"><i class="fas fa-chevron-left flip-rtl"></i></span>
  @else
  <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}"><i class="fas fa-chevron-left flip-rtl"></i></a>
  @endif

  @foreach($elements as $el)
    @if(is_string($el))<span>{{ $el }}</span>@endif
    @if(is_array($el))
      @foreach($el as $page => $url)
        @if($page == $paginator->currentPage())
        <span class="cur">{{ $page }}</span>
        @else
        <a href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach
    @endif
  @endforeach

  @if($paginator->hasMorePages())
  <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}"><i class="fas fa-chevron-right flip-rtl"></i></a>
  @else
  <span style="opacity:.3;cursor:default"><i class="fas fa-chevron-right flip-rtl"></i></span>
  @endif
</div>
@endif
