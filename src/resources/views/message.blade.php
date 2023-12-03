@extends('support::message.layout')

@section('title', __('tips'))


@section('message')

    <div class="msg {{ $status ?? 'success' }}">
        {{ $msg ?? '' }}
    </div>

@endsection

@section('url')

    <div class="url">
        <a href="{!! $redirect ?? 'javascript:history.back();' !!}">[ ok ]</a>
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        window.onload = function () {
            let timer = "{{ $count_down ?? 3 }}";
            const href = "{!! $redirect ?? 'javascript:history.back();' !!}";
            const interval = setInterval(function () {
                --timer;
                console.log('倒计时', timer);
                if (timer <= 0) {
                    window.location.href = href;
                    clearInterval(interval);
                }
            }, 1000);
        };
    </script>
@endsection
