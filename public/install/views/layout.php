<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugs Framework - <?= $controller->getStepTitle($step) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700;800&family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="assets/install.css">
</head>

<body>
    <div class="installer-container">
        <!-- Header -->
        <header class="installer-header animate-in">
            <div class="brand">
                <span class="brand-icon">⚡</span>
                <span class="brand-text">Plugs</span>
            </div>
            <div class="header-subtitle">Next-Gen PHP Framework</div>
        </header>

        <!-- Progress Steps -->
        <div class="progress-container animate-in stagger-1">
            <div class="progress-bar-wrapper">
                <div class="progress-fill" style="width: <?= ($step / 5) * 100 ?>%"></div>
            </div>
            <div class="steps">
                <?php
                $steps = [
                    1 => ['label' => 'System', 'icon' => 'fa-server'],
                    2 => ['label' => 'Database', 'icon' => 'fa-database'],
                    3 => ['label' => 'App', 'icon' => 'fa-cog'],
                    4 => ['label' => 'Admin', 'icon' => 'fa-user-shield'],
                    5 => ['label' => 'Ready', 'icon' => 'fa-rocket'],
                ];
                foreach ($steps as $i => $data):
                    $isActive = $step >= $i;
                    $isCompleted = $step > $i;
                    ?>
                    <div class="step <?= $isActive ? 'active' : '' ?> <?= $isCompleted ? 'completed' : '' ?>">
                        <div class="step-number">
                            <?php if ($isCompleted): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?= $i ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-label"><?= $data['label'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content -->
        <main class="installer-main animate-in stagger-2">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>

        <!-- Footer -->
        <footer class="installer-footer animate-in stagger-3">
            <p>&copy; <?= date('Y') ?> <a href="https://github.com/celionatti/plugs" target="_blank">Plugs
                    Framework</a>. Crafted with precision.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="assets/install.js"></script>
</body>

</html>