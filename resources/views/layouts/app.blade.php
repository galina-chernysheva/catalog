<!-- Хранится в resources/views/layouts/app.blade.php -->

<?php
    $appName = config('app.name');
?>

<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @hasSection ('title')
            {{ $appName }} | @yield('title')
        @else
            {{ $appName }}
        @endif
    </title>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
    {{--<script type="text/javascript" src="{{ asset('js/app.js') }}"></script>--}}
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="navbar-header">
                    <span class="h3 navbar-brand mb-0">{{ $appName }}</span>
                </div>
            </div>
        </nav>
    </div>
    <div class="container">
        @yield('breadcrumbs')
    </div>
    <div class="container">
        <div class="content">
            <h2 class="header">@yield('header')</h2>
            @yield('content')
        </div>
    </div>
</body>
</html>
