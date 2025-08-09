<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Handler;

use Throwable;
use Plugs\Plugs;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

class ExceptionHandler
{
    /**
     * The application instance.
     */
    protected Plugs $app;

    /**
     * Create a new exception handler instance.
     */
    public function __construct(Plugs $app)
    {
        $this->app = $app;
    }

    /**
     * Render an exception as an HTTP response.
     */
    public function render(Request $request, Throwable $exception): Response
    {
        if ($this->app->isEnvironment('local', 'testing')) {
            return $this->renderDebugResponse($exception, $request);
        }

        return $this->renderProductionResponse($exception);
    }

    /**
     * Render a debug response for development.
     */
    protected function renderDebugResponse(Throwable $exception, Request $request): Response
    {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $codeSnippet = $this->getCodeSnippet($file, $line, 10);
        $requestData = $this->getRequestData($request);
        $environment = $this->getEnvironmentData();

        ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(get_class($exception)) ?> - Plugs Framework</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            --success-gradient: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            --warning-gradient: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
            
            --bg-primary: #0a0e27;
            --bg-secondary: #1a1f3a;
            --bg-tertiary: #252b4a;
            --bg-card: #2d3451;
            --bg-elevated: #363d5c;
            
            --text-primary: #ffffff;
            --text-secondary: #b8c5d1;
            --text-muted: #8892a6;
            --text-accent: #667eea;
            
            --border-primary: #3a4562;
            --border-secondary: #4a5578;
            --border-accent: rgba(102, 126, 234, 0.3);
            
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 8px 25px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.2);
            --shadow-xl: 0 20px 45px rgba(0, 0, 0, 0.25);
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --spacing-xl: 3rem;
            --spacing-2xl: 4rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 107, 107, 0.03) 0%, transparent 50%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: var(--spacing-lg);
        }

        /* Header Styles */
        .header {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl) var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: var(--spacing-xl);
            align-items: start;
        }

        .brand-section {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .framework-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            background: var(--primary-gradient);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            box-shadow: var(--shadow-md);
        }

        .brand-text {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-tagline {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .exception-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .exception-title {
            font-size: clamp(1.5rem, 3vw, 2.5rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
            line-height: 1.2;
        }

        .exception-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
        }

        .meta-section {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .env-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .env-development {
            background: var(--warning-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-xs) var(--spacing-sm);
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            color: #ff6b6b;
            font-weight: 500;
        }

        .exception-class {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            color: var(--text-accent);
            background: rgba(102, 126, 234, 0.1);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-accent);
        }

        .file-location {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: var(--spacing-xl);
            align-items: start;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: sticky;
            top: var(--spacing-lg);
        }

        .nav-card {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-md);
        }

        .nav-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .nav-tabs {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .nav-tab {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            padding: var(--spacing-sm) var(--spacing-md);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            text-align: left;
            position: relative;
        }

        .nav-tab:hover {
            color: var(--text-primary);
            background: var(--bg-elevated);
            transform: translateX(4px);
        }

        .nav-tab.active {
            color: var(--text-primary);
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid var(--border-accent);
        }

        .nav-tab.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-gradient);
            border-radius: 0 2px 2px 0;
        }

        .nav-tab i {
            width: 20px;
            text-align: center;
            opacity: 0.7;
        }

        .nav-tab.active i {
            opacity: 1;
        }

        /* Content Panel */
        .content-panel {
            background: var(--bg-card);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .content-header {
            background: var(--bg-elevated);
            padding: var(--spacing-lg) var(--spacing-xl);
            border-bottom: 1px solid var(--border-primary);
        }

        .content-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .content-body {
            padding: var(--spacing-xl);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Code Styles */
        .code-container {
            background: var(--bg-primary);
            border: 1px solid var(--border-secondary);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: var(--spacing-lg);
        }

        .code-header {
            background: var(--bg-secondary);
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .code-file-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .copy-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.825rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .copy-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .code-content {
            padding: var(--spacing-lg);
            overflow-x: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            line-height: 1.6;
        }

        .line-numbers {
            display: table;
            width: 100%;
        }

        .line {
            display: table-row;
        }

        .line-number {
            display: table-cell;
            text-align: right;
            padding-right: var(--spacing-md);
            color: var(--text-muted);
            user-select: none;
            width: 60px;
            vertical-align: top;
            border-right: 1px solid var(--border-primary);
        }

        .line-content {
            display: table-cell;
            padding-left: var(--spacing-md);
            white-space: pre-wrap;
            word-break: break-all;
        }

        .line-highlight {
            background: rgba(255, 107, 107, 0.1);
            border-left: 4px solid #ff6b6b;
        }

        .line-highlight .line-number {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            font-weight: 600;
        }

        /* Stack Trace Styles */
        .search-container {
            margin-bottom: var(--spacing-lg);
        }

        .search-box {
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--text-accent);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-box::placeholder {
            color: var(--text-muted);
        }

        .trace-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .trace-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-md);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .trace-item:hover {
            border-color: var(--border-secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .trace-header {
            padding: var(--spacing-md) var(--spacing-lg);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .trace-header:hover {
            background: var(--bg-tertiary);
        }

        .trace-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .trace-function {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--text-accent);
            font-size: 0.95rem;
        }

        .trace-location {
            font-family: 'JetBrains Mono', monospace;
            color: var(--text-muted);
            font-size: 0.825rem;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .trace-item.expanded .toggle-icon {
            transform: rotate(90deg);
        }

        .trace-details {
            display: none;
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-primary);
            background: var(--bg-primary);
        }

        .trace-item.expanded .trace-details {
            display: block;
        }

        /* Data Table Styles */
        .data-section {
            margin-bottom: var(--spacing-xl);
        }

        .section-header {
            margin-bottom: var(--spacing-md);
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-accent);
            margin-bottom: var(--spacing-xs);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-md);
            overflow: hidden;
        }

        .data-table th,
        .data-table td {
            padding: var(--spacing-sm) var(--spacing-md);
            text-align: left;
            border-bottom: 1px solid var(--border-primary);
            vertical-align: top;
        }

        .data-table th {
            background: var(--bg-tertiary);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            color: var(--text-secondary);
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.825rem;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: var(--bg-tertiary);
        }

        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1.125rem;
            margin-bottom: var(--spacing-xs);
        }

        .empty-state .empty-description {
            font-size: 0.95rem;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 300px 1fr;
                gap: var(--spacing-lg);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: var(--spacing-md);
            }

            .header-content {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
                text-align: center;
            }

            .exception-meta {
                justify-content: center;
            }

            .main-content {
                grid-template-columns: 1fr;
                gap: var(--spacing-lg);
            }

            .sidebar {
                position: static;
            }

            .nav-tabs {
                flex-direction: row;
                overflow-x: auto;
                gap: var(--spacing-xs);
            }

            .nav-tab {
                white-space: nowrap;
                min-width: fit-content;
            }

            .content-body {
                padding: var(--spacing-lg);
            }

            .trace-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-sm);
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: var(--spacing-lg);
            }

            .framework-brand {
                flex-direction: column;
                text-align: center;
            }

            .exception-meta {
                flex-direction: column;
                align-items: stretch;
            }

            .data-table {
                font-size: 0.75rem;
            }
        }

        /* Utility Classes */
        .highlight {
            background: var(--warning-gradient);
            color: white;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-content">
                <div class="brand-section">
                    <div class="framework-brand">
                        <div class="brand-logo">🔌</div>
                        <div class="brand-text">
                            <div class="brand-name">Plugs Framework</div>
                            <div class="brand-tagline">Modern PHP Development</div>
                        </div>
                    </div>
                    <div class="env-badge env-development">
                        <i class="fas fa-code"></i>
                        <?= ucfirst($this->app->environment() ?? 'development') ?> Mode
                    </div>
                </div>
                
                <div class="exception-info">
                    <h1 class="exception-title"><?= htmlspecialchars($exception->getMessage()) ?></h1>
                    <div class="exception-meta">
                        <div class="status-indicator">
                            <i class="fas fa-exclamation-triangle"></i>
                            Exception Thrown
                        </div>
                        <div class="exception-class">
                            <?= htmlspecialchars(get_class($exception)) ?>
                        </div>
                        <div class="file-location">
                            <i class="fas fa-file-code"></i>
                            <?= htmlspecialchars(basename($file)) ?>
                            <span>:</span>
                            <span><?= $line ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Sidebar Navigation -->
            <div class="sidebar">
                <div class="nav-card">
                    <div class="nav-title">
                        <i class="fas fa-compass"></i>
                        Navigation
                    </div>
                    <div class="nav-tabs">
                        <button class="nav-tab active" onclick="showTab('stack-trace')">
                            <i class="fas fa-layer-group"></i>
                            Stack Trace
                        </button>
                        <button class="nav-tab" onclick="showTab('code-snippet')">
                            <i class="fas fa-code"></i>
                            Source Code
                        </button>
                        <button class="nav-tab" onclick="showTab('request-info')">
                            <i class="fas fa-globe"></i>
                            Request Data
                        </button>
                        <button class="nav-tab" onclick="showTab('environment')">
                            <i class="fas fa-server"></i>
                            Environment
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content Panel -->
            <div class="content-panel">
                <!-- Stack Trace Tab -->
                <div id="stack-trace" class="tab-content active">
                    <div class="content-header">
                        <div class="content-title">
                            <i class="fas fa-layer-group"></i>
                            Stack Trace Analysis
                        </div>
                    </div>
                    <div class="content-body">
                        <div class="search-container">
                            <input type="text" class="search-box" placeholder="Search stack trace..." onkeyup="filterTrace(this.value)">
                        </div>
                        <div class="trace-list">
                            <?php foreach ($exception->getTrace() as $index => $trace): ?>
                                <div class="trace-item" data-search="<?= htmlspecialchars(($trace['file'] ?? '') . ($trace['function'] ?? '')) ?>">
                                    <div class="trace-header" onclick="toggleTrace(this)">
                                        <div class="trace-info">
                                            <div class="trace-function">
                                                <?= isset($trace['class']) ? htmlspecialchars($trace['class']) . '::' : '' ?>
                                                <?= isset($trace['function']) ? htmlspecialchars($trace['function']) . '()' : '[main execution]' ?>
                                            </div>
                                            <div class="trace-location">
                                                <?= isset($trace['file']) ? htmlspecialchars($trace['file']) : '[internal function]' ?>
                                                <?= isset($trace['line']) ? ':' . $trace['line'] : '' ?>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-right toggle-icon"></i>
                                    </div>
                                    <div class="trace-details">
                                        <?php if (isset($trace['file']) && isset($trace['line'])): ?>
                                            <div class="code-container">
                                                <div class="code-header">
                                                    <div class="code-file-info">
                                                        <i class="fas fa-file-code"></i>
                                                        <?= htmlspecialchars(basename($trace['file'])) ?> around line <?= $trace['line'] ?>
                                                    </div>
                                                    <button class="copy-btn" onclick="copyCode(this)">
                                                        <i class="fas fa-copy"></i> Copy
                                                    </button>
                                                </div>
                                                <div class="code-content">
                                                    <?= $this->getCodeSnippet($trace['file'], $trace['line'], 3) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($trace['args']) && !empty($trace['args'])): ?>
                                            <div class="section-title">Function Arguments</div>
                                            <div class="code-container">
                                                <div class="code-content">
                                                    <pre><?= htmlspecialchars(var_export($trace['args'], true)) ?></pre>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Code Snippet Tab -->
                <div id="code-snippet" class="tab-content">
                    <div class="content-header">
                        <div class="content-title">
                            <i class="fas fa-code"></i>
                            Source Code Context
                        </div>
                    </div>
                    <div class="content-body">
                        <div class="code-container">
                            <div class="code-header">
                                <div class="code-file-info">
                                    <i class="fas fa-file-code"></i>
                                    <?= htmlspecialchars($file) ?>
                                    <span>around line <?= $line ?></span>
                                </div>
                                <button class="copy-btn" onclick="copyCode(this)">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="code-content">
                                <?= $codeSnippet ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Info Tab -->
                <div id="request-info" class="tab-content">
                    <div class="content-header">
                        <div class="content-title">
                            <i class="fas fa-globe"></i>
                            Request Information
                        </div>
                    </div>
                    <div class="content-body">
                        <?php if (!empty($requestData)): ?>
                            <?php foreach ($requestData as $section => $data): ?>
                                <div class="data-section">
                                    <div class="section-header">
                                        <div class="section-title">
                                            <?= ucwords(str_replace('_', ' ', $section)) ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($data)): ?>
                                        <table class="data-table">
                                            <thead>
                                                <tr>
                                                    <th>Key</th>
                                                    <th>Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $key => $value): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($key) ?></td>
                                                        <td><?= htmlspecialchars(is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : (string)$value) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>No Data Available</p>
                                            <div class="empty-description">No <?= strtolower(str_replace('_', ' ', $section)) ?> data was found for this request.</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-globe"></i>
                                <p>No Request Data</p>
                                <div class="empty-description">No request information is available for this exception.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Environment Tab -->
                <div id="environment" class="tab-content">
                    <div class="content-header">
                        <div class="content-title">
                            <i class="fas fa-server"></i>
                            System Environment
                        </div>
                    </div>
                    <div class="content-body">
                        <div class="data-section">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Variable</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($environment as $key => $value): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($key) ?></td>
                                            <td><?= htmlspecialchars(is_array($value) ? json_encode($value) : (string)$value) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Stack trace toggle functionality
        function toggleTrace(element) {
            const traceItem = element.parentElement;
            traceItem.classList.toggle('expanded');
        }

        // Search functionality for stack trace
        function filterTrace(searchTerm) {
            const traceItems = document.querySelectorAll('.trace-item');
            const term = searchTerm.toLowerCase();
            
            traceItems.forEach(item => {
                const searchText = item.getAttribute('data-search').toLowerCase();
                if (searchText.includes(term) || term === '') {
                    item.style.display = 'block';
                    // Remove previous highlights
                    item.innerHTML = item.innerHTML.replace(/<span class="highlight">/g, '').replace(/<\/span>/g, '');
                    
                    // Add highlights for search terms
                    if (term && term.length > 0) {
                        const regex = new RegExp(`(${term})`, 'gi');
                        const traceFunction = item.querySelector('.trace-function');
                        const traceLocation = item.querySelector('.trace-location');
                        
                        if (traceFunction) {
                            traceFunction.innerHTML = traceFunction.textContent.replace(regex, '<span class="highlight">$1</span>');
                        }
                        if (traceLocation) {
                            traceLocation.innerHTML = traceLocation.textContent.replace(regex, '<span class="highlight">$1</span>');
                        }
                    }
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Copy code functionality
        function copyCode(button) {
            const codeContent = button.closest('.code-container').querySelector('.code-content');
            const text = codeContent.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.style.background = 'var(--success-gradient)';
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.style.background = 'var(--primary-gradient)';
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                const originalContent = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.style.background = 'var(--success-gradient)';
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.style.background = 'var(--primary-gradient)';
                }, 2000);
            });
        }

        // Auto-expand first trace item on load
        document.addEventListener('DOMContentLoaded', function() {
            const firstTrace = document.querySelector('.trace-item');
            if (firstTrace) {
                firstTrace.classList.add('expanded');
            }

            // Add smooth scrolling for better UX
            document.documentElement.style.scrollBehavior = 'smooth';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchBox = document.querySelector('.search-box');
                if (searchBox) {
                    searchBox.focus();
                    searchBox.select();
                }
            }
            
            // Tab navigation with arrow keys
            if (e.altKey) {
                const tabs = Array.from(document.querySelectorAll('.nav-tab'));
                const activeTabIndex = tabs.findIndex(tab => tab.classList.contains('active'));
                
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextTab = tabs[(activeTabIndex + 1) % tabs.length];
                    nextTab.click();
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevTab = tabs[(activeTabIndex - 1 + tabs.length) % tabs.length];
                    prevTab.click();
                }
            }
        });

        // Enhanced search with debouncing
        let searchTimeout;
        const originalFilterTrace = filterTrace;
        filterTrace = function(searchTerm) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                originalFilterTrace(searchTerm);
            }, 300);
        };

        // Add loading states and animations
        function showLoadingState(element) {
            element.style.opacity = '0.6';
            element.style.pointerEvents = 'none';
        }

        function hideLoadingState(element) {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
        }

        // Add intersection observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeIn 0.6s ease-out';
                }
            });
        }, observerOptions);

        // Observe trace items for stagger animation
        document.addEventListener('DOMContentLoaded', function() {
            const traceItems = document.querySelectorAll('.trace-item');
            traceItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
                observer.observe(item);
            });
        });
    </script>
</body>
</html>
<?php
        $html = ob_get_clean();

        return new Response($html, 500, ['Content-Type' => 'text/html']);
    }

    /**
     * Render a production response.
     */
    protected function renderProductionResponse(Throwable $exception): Response
    {
        return new Response('Internal Server Error', 500);
    }

    /**
     * Report the exception to logging services.
     */
    public function report(Throwable $exception): void
    {
        // Log the exception
        error_log($exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    }

    /**
     * Get code snippet with line numbers and highlighting.
     */
    protected function getCodeSnippet(string $file, int $line, int $padding = 5): string
    {
        if (!is_readable($file)) {
            return '<div class="empty-state"><i class="fas fa-file-slash"></i><p>File Not Readable</p><div class="empty-description">The source file could not be read or does not exist.</div></div>';
        }

        $lines = file($file);
        $start = max($line - $padding - 1, 0);
        $end = min($line + $padding, count($lines));
        $snippet = '<div class="line-numbers">';

        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $codeLine = htmlspecialchars(rtrim($lines[$i], "\r\n"));
            $isHighlight = $currentLine === $line;
            
            $snippet .= sprintf(
                '<div class="line %s"><div class="line-number">%d</div><div class="line-content">%s</div></div>',
                $isHighlight ? 'line-highlight' : '',
                $currentLine,
                $codeLine ?: ' '
            );
        }

        $snippet .= '</div>';
        return $snippet;
    }

    /**
     * Get request data for display.
     */
    protected function getRequestData(Request $request): array
    {
        return [
            'headers' => $request->headers() ?? [],
            'get_parameters' => $_GET ?? [],
            'post_data' => $_POST ?? [],
            'server_info' => array_filter($_SERVER, function($key) {
                return in_array($key, [
                    'REQUEST_METHOD', 'REQUEST_URI', 'HTTP_HOST', 'HTTP_USER_AGENT',
                    'HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_ENCODING',
                    'HTTP_CONNECTION', 'HTTP_REFERER', 'REMOTE_ADDR', 'REQUEST_TIME',
                    'HTTPS', 'SERVER_PORT', 'QUERY_STRING', 'CONTENT_TYPE', 'CONTENT_LENGTH'
                ]);
            }, ARRAY_FILTER_USE_KEY),
            'cookies' => $_COOKIE ?? []
        ];
    }

    /**
     * Get environment data for display.
     */
    protected function getEnvironmentData(): array
    {
        return [
            'PHP Version' => PHP_VERSION,
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Operating System' => PHP_OS,
            'Architecture' => php_uname('m'),
            'Memory Limit' => ini_get('memory_limit'),
            'Memory Usage' => $this->formatBytes(memory_get_usage(true)),
            'Peak Memory Usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'Max Execution Time' => ini_get('max_execution_time') . ' seconds',
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Max Input Vars' => ini_get('max_input_vars'),
            'Timezone' => date_default_timezone_get(),
            'Current Time' => date('Y-m-d H:i:s T'),
            'Server Time' => date('c'),
            'Environment' => $this->app->environment() ?? 'unknown',
            'Debug Mode' => $this->app->isEnvironment('local', 'testing') ? 'Enabled' : 'Disabled',
            'Error Reporting' => $this->getErrorReportingLevel(),
            'Extensions Loaded' => count(get_loaded_extensions()),
            'Zend Version' => zend_version(),
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Get human readable error reporting level.
     */
    protected function getErrorReportingLevel(): string
    {
        $level = error_reporting();
        if ($level === E_ALL) return 'All Errors';
        if ($level === 0) return 'None';
        if ($level === (E_ALL & ~E_NOTICE)) return 'All except Notices';
        if ($level === (E_ALL & ~E_NOTICE & ~E_WARNING)) return 'Fatal Errors Only';
        return 'Custom (' . $level . ')';
    }
}