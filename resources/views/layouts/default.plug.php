<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ $_SESSION['csrf_token'] ?? '' }}">
    <title>@yield('title', 'Plugs Modern PHP Framework') - {{ $app_name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    @stack('styles')
</head>

<body class="bg-white">
    <Header />

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <Footer />

    @stack('scripts')
</body>

</html>