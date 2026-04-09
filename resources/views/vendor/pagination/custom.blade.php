@if($paginator->hasPages())
<div class="pager">
  @if($paginator->onFirstPage())
  <span style="opacity:.3;cursor:default"><i class="fas fa-chevron-left"></i></span>
  @else
  <a href="{{ $paginator->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>
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
  <a href="{{ $paginator->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>
  @else
  <span style="opacity:.3;cursor:default"><i class="fas fa-chevron-right"></i></span>
  @endif
</div>
@endif
