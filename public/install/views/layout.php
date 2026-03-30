<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugs Framework - <?= $controller->getStepTitle($step) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                        }
                    },
                    fontFamily: {
                        jakarta: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #fcfaf8;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .step-active {
            color: #10b981;
            border-bottom: 2px solid #10b981;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
        <!-- Header -->
        <header class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-extrabold font-outfit tracking-tight text-gray-900">Plugs</h1>
            </div>
            <p class="text-gray-500 font-medium">Next-Gen Framework Installation</p>
        </header>

        <div class="glass-panel rounded-[2.5rem] shadow-2xl shadow-emerald-900/5 overflow-hidden border border-white">
            <!-- Progress Bar -->
            <div class="bg-white/50 border-b border-gray-100 px-8 py-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold uppercase tracking-widest text-emerald-600">Step <?= $step ?> / 5</span>
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400"><?= $controller->getStepTitle($step) ?></span>
                </div>
                <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000 ease-out" style="width: <?= ($step / 5) * 100 ?>%"></div>
                </div>
            </div>

            <!-- Content Area -->
            <main class="p-8 lg:p-12">
                <?php if (!empty($error)): ?>
                    <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-red-600">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="font-medium"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <?php 
                $message = $_SESSION['install_message'] ?? null;
                unset($_SESSION['install_message']);
                if (!empty($message)): 
                ?>
                    <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-3 text-emerald-600">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-medium"><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </main>

            <!-- Footer -->
            <footer class="bg-gray-50/50 border-t border-gray-100 px-8 py-6 text-center text-sm text-gray-400">
                <p>&copy; <?= date('Y') ?> <a href="https://github.com/celionatti/plugs" class="hover:text-emerald-500 font-medium">Plugs Framework</a>. Built for efficiency.</p>
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/install.js"></script>
</body>

</html>
