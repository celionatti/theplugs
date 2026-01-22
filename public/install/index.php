<?php

declare(strict_types=1);

/**
 * Plugs Framework Installer
 * 
 * This is the entry point for the installation wizard.
 * After installation, this folder should be deleted for security.
 */

// Prevent direct access if already installed, except for the final success step
if (file_exists(__DIR__ . '/../../plugs.lock') && ($_GET['step'] ?? 1) != 5) {
    header('Location: /');
    exit;
}

// Start session for installer
session_start();

// Define installer constants
define('INSTALL_PATH', __DIR__ . '/');
define('ROOT_PATH', dirname(__DIR__, 2) . '/');
define('TEMPLATES_PATH', INSTALL_PATH . 'templates/');

// Load installer configuration
$config = require INSTALL_PATH . 'config.php';

// Load installer controller
require INSTALL_PATH . 'InstallController.php';

// Initialize controller
$controller = new InstallController($config);

// Handle the request
$step = $_GET['step'] ?? 1;
$step = max(1, min(5, (int) $step));

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle special actions
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        header('Content-Type: application/json');

        if ($action === 'cleanup') {
            echo json_encode($controller->cleanupInstallation());
            exit;
        }

        if ($action === 'composer') {
            echo json_encode($controller->installComposer());
            exit;
        }
    }

    $result = $controller->processStep($step, $_POST);

    if ($result['success']) {
        if ($step < 5) {
            header('Location: ?step=' . ($step + 1));
            exit;
        }
    } else {
        $error = $result['error'] ?? 'An error occurred';
    }
}

// Get step data
$stepData = $controller->getStepData($step);
$requirements = $step === 1 ? $controller->checkRequirements() : [];

// Render the view
$viewFile = INSTALL_PATH . 'views/step-' . $step . '.php';
$layoutFile = INSTALL_PATH . 'views/layout.php';

ob_start();
include $viewFile;
$content = ob_get_clean();

include $layoutFile;
