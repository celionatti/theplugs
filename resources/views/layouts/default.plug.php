<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Plugs Modern PHP Framework') - {{ $app_name }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/global.css') }}">
    @stack('styles')
</head>

<body>
    <div class="particles" id="particles"></div>
    <div id="app-content">
        @yield('content')
    </div>

    <script src="{{ asset('assets/js/global.js') }}"></script>
    @stack('scripts')
    <script src="{{ asset('assets/js/plugs-spa.js') }}"></script>
</body>

</html>