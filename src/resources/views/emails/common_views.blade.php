@if(isset($is_html) && $is_html == 1)
    {!! $content !!}
@else
    {!! strip_tags($content) !!}
@endif
