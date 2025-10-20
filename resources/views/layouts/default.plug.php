<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ $_SESSION['csrf_token'] ?? '' }}">
    <title>@yield('title', 'Plugs Modern PHP Framework') - {{ $app_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    @stack('styles')
</head>

<body>

    <main class="main-container">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>