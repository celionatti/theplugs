<?php

declare(strict_types=1);

namespace Plugs\Exceptions\Handler;

use Throwable;
use Plugs\Plugs;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;

class ExceptionHandler
{
    protected Plugs $app;

    public function __construct(Plugs $app)
    {
        $this->app = $app;
    }

    public function render(Request $request, Throwable $exception): Response
    {
        if ($this->app->isEnvironment('local', 'testing')) {
            return $this->renderDebugResponse($exception, $request);
        }

        return $this->renderProductionResponse($exception);
    }

    protected function renderDebugResponse(Throwable $exception, Request $request): Response
    {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTrace();
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
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #0f172a;
                    color: #e2e8f0;
                    line-height: 1.6;
                    min-height: 100vh;
                }

                .container {
                    max-width: 1600px;
                    margin: 0 auto;
                    padding: 24px;
                }

                /* Header */
                .header {
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    border-radius: 16px;
                    padding: 32px;
                    margin-bottom: 24px;
                    box-shadow: 0 10px 40px rgba(239, 68, 68, 0.3);
                    position: relative;
                    overflow: hidden;
                }

                .header::before {
                    content: '';
                    position: absolute;
                    top: -50%;
                    right: -50%;
                    width: 200%;
                    height: 200%;
                    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
                    animation: pulse 4s ease-in-out infinite;
                }

                @keyframes pulse {

                    0%,
                    100% {
                        transform: scale(1);
                        opacity: 0.5;
                    }

                    50% {
                        transform: scale(1.1);
                        opacity: 0.3;
                    }
                }

                .header-content {
                    position: relative;
                    z-index: 1;
                }

                .brand {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 20px;
                }

                .brand-logo {
                    width: 48px;
                    height: 48px;
                    background: rgba(255, 255, 255, 0.2);
                    backdrop-filter: blur(10px);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    font-weight: 800;
                    color: white;
                    border: 2px solid rgba(255, 255, 255, 0.3);
                }

                .brand-text h1 {
                    font-size: 24px;
                    font-weight: 700;
                    color: white;
                }

                .brand-text p {
                    font-size: 13px;
                    color: rgba(255, 255, 255, 0.8);
                }

                .exception-message {
                    font-size: 28px;
                    font-weight: 700;
                    color: white;
                    margin: 20px 0 16px 0;
                    line-height: 1.3;
                }

                .exception-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 12px;
                    align-items: center;
                }

                .badge {
                    padding: 6px 12px;
                    border-radius: 8px;
                    font-size: 13px;
                    font-weight: 600;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                }

                .badge-error {
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                }

                .badge-file {
                    background: rgba(0, 0, 0, 0.3);
                    color: white;
                    font-family: monospace;
                }

                /* Grid Layout */
                .main-grid {
                    display: grid;
                    grid-template-columns: 380px 1fr;
                    gap: 24px;
                    align-items: start;
                }

                /* Sidebar */
                .sidebar {
                    position: sticky;
                    top: 24px;
                    display: flex;
                    flex-direction: column;
                    gap: 16px;
                }

                .info-panel {
                    background: #1e293b;
                    border: 1px solid #334155;
                    border-radius: 12px;
                    overflow: hidden;
                }

                .panel-header {
                    background: #0f172a;
                    padding: 14px 18px;
                    border-bottom: 1px solid #334155;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .panel-header h3 {
                    font-size: 14px;
                    font-weight: 600;
                    color: #f1f5f9;
                }

                .panel-icon {
                    font-size: 16px;
                }

                .panel-content {
                    padding: 18px;
                }

                .panel-content.no-padding {
                    padding: 0;
                }

                .info-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #334155;
                    font-size: 13px;
                }

                .info-row:last-child {
                    border-bottom: none;
                }

                .info-label {
                    color: #94a3b8;
                    font-weight: 500;
                }

                .info-value {
                    color: #f1f5f9;
                    font-weight: 600;
                    font-family: monospace;
                    font-size: 12px;
                }

                .env-badge {
                    background: #10b981;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    text-transform: uppercase;
                    font-weight: 700;
                }

                /* Code Context in Sidebar */
                .code-context-file {
                    padding: 10px 18px;
                    background: #0f172a;
                    font-size: 11px;
                    font-weight: 600;
                    color: #94a3b8;
                    font-family: monospace;
                    border-bottom: 1px solid #334155;
                }

                .code-lines-mini {
                    max-height: 300px;
                    overflow-y: auto;
                    font-family: monospace;
                    font-size: 12px;
                }

                .code-line-mini {
                    display: flex;
                    padding: 4px 0;
                    transition: background 0.2s;
                }

                .code-line-mini:hover {
                    background: rgba(100, 116, 139, 0.1);
                }

                .code-line-mini.highlight {
                    background: rgba(239, 68, 68, 0.15);
                    border-left: 3px solid #ef4444;
                }

                .line-num-mini {
                    color: #475569;
                    padding: 0 12px 0 18px;
                    text-align: right;
                    min-width: 50px;
                    user-select: none;
                }

                .line-code-mini {
                    color: #cbd5e1;
                    padding-right: 18px;
                    flex: 1;
                    white-space: pre;
                    overflow-x: auto;
                }

                /* Stack Trace in Sidebar */
                .stack-list {
                    max-height: 400px;
                    overflow-y: auto;
                }

                .stack-item {
                    padding: 12px 18px;
                    border-bottom: 1px solid #334155;
                    cursor: pointer;
                    transition: background 0.2s;
                }

                .stack-item:hover {
                    background: rgba(100, 116, 139, 0.1);
                }

                .stack-item:last-child {
                    border-bottom: none;
                }

                .stack-number {
                    background: #ef4444;
                    color: white;
                    width: 22px;
                    height: 22px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 11px;
                    font-weight: 700;
                    margin-right: 10px;
                }

                .stack-func {
                    font-size: 13px;
                    font-weight: 600;
                    color: #60a5fa;
                    font-family: monospace;
                    margin-bottom: 4px;
                    word-break: break-all;
                }

                .stack-loc {
                    font-size: 11px;
                    color: #94a3b8;
                    font-family: monospace;
                }

                /* Main Content */
                .main-content {
                    background: #1e293b;
                    border: 1px solid #334155;
                    border-radius: 12px;
                    overflow: hidden;
                }

                .tabs-nav {
                    background: #0f172a;
                    border-bottom: 1px solid #334155;
                    padding: 0 24px;
                    display: flex;
                    gap: 8px;
                    overflow-x: auto;
                }

                .tab-btn {
                    background: none;
                    border: none;
                    color: #94a3b8;
                    padding: 16px 20px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    border-bottom: 3px solid transparent;
                    transition: all 0.3s;
                    white-space: nowrap;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .tab-btn:hover {
                    color: #f1f5f9;
                    background: rgba(100, 116, 139, 0.1);
                }

                .tab-btn.active {
                    color: #ef4444;
                    border-bottom-color: #ef4444;
                }

                .tab-content {
                    display: none;
                    padding: 24px;
                    animation: fadeIn 0.3s;
                }

                .tab-content.active {
                    display: block;
                }

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                /* Code Block */
                .code-block {
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-radius: 12px;
                    overflow: hidden;
                    margin-bottom: 24px;
                }

                .code-header {
                    background: #1e293b;
                    padding: 12px 18px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px solid #334155;
                }

                .code-title {
                    font-size: 13px;
                    font-family: monospace;
                    color: #94a3b8;
                    font-weight: 600;
                }

                .copy-btn {
                    background: #ef4444;
                    color: white;
                    border: none;
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .copy-btn:hover {
                    background: #dc2626;
                    transform: translateY(-1px);
                }

                .code-body {
                    padding: 18px;
                    overflow-x: auto;
                    font-family: monospace;
                    font-size: 14px;
                    line-height: 1.6;
                    max-height: 600px;
                    overflow-y: auto;
                }

                .code-line {
                    display: flex;
                }

                .code-line.highlight {
                    background: rgba(239, 68, 68, 0.15);
                    border-left: 4px solid #ef4444;
                }

                .line-num {
                    color: #475569;
                    padding-right: 16px;
                    text-align: right;
                    min-width: 60px;
                    user-select: none;
                    border-right: 1px solid #334155;
                }

                .line-code {
                    padding-left: 16px;
                    color: #cbd5e1;
                    flex: 1;
                }

                /* Data Table */
                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-radius: 12px;
                    overflow: hidden;
                    margin-bottom: 24px;
                }

                .data-table thead {
                    background: #1e293b;
                }

                .data-table th {
                    padding: 12px 16px;
                    text-align: left;
                    font-size: 12px;
                    font-weight: 700;
                    color: #f1f5f9;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #334155;
                }

                .data-table td {
                    padding: 12px 16px;
                    font-size: 13px;
                    color: #cbd5e1;
                    border-bottom: 1px solid #334155;
                    font-family: monospace;
                    word-break: break-word;
                }

                .data-table tr:last-child td {
                    border-bottom: none;
                }

                .data-table tbody tr:hover {
                    background: rgba(100, 116, 139, 0.1);
                }

                .section-title {
                    font-size: 18px;
                    font-weight: 700;
                    color: #f1f5f9;
                    margin-bottom: 16px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #334155;
                }

                /* Search Box */
                .search-box {
                    width: 100%;
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-radius: 8px;
                    padding: 12px 16px;
                    color: #f1f5f9;
                    font-size: 14px;
                    margin-bottom: 20px;
                    transition: all 0.3s;
                }

                .search-box:focus {
                    outline: none;
                    border-color: #ef4444;
                    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
                }

                .search-box::placeholder {
                    color: #64748b;
                }

                /* Trace Item Expandable */
                .trace-expandable {
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-radius: 12px;
                    margin-bottom: 16px;
                    overflow: hidden;
                }

                .trace-header-expand {
                    padding: 16px 20px;
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    transition: background 0.2s;
                }

                .trace-header-expand:hover {
                    background: #1e293b;
                }

                .trace-details {
                    display: none;
                    padding: 20px;
                    border-top: 1px solid #334155;
                    background: #1e293b;
                }

                .trace-expandable.expanded .trace-details {
                    display: block;
                }

                .toggle-icon {
                    transition: transform 0.3s;
                    color: #64748b;
                }

                .trace-expandable.expanded .toggle-icon {
                    transform: rotate(90deg);
                }

                /* Empty State */
                .empty-state {
                    text-align: center;
                    padding: 60px 20px;
                    color: #64748b;
                }

                .empty-state-icon {
                    font-size: 48px;
                    margin-bottom: 16px;
                    opacity: 0.5;
                }

                /* Scrollbar */
                ::-webkit-scrollbar {
                    width: 8px;
                    height: 8px;
                }

                ::-webkit-scrollbar-track {
                    background: #0f172a;
                }

                ::-webkit-scrollbar-thumb {
                    background: #475569;
                    border-radius: 4px;
                }

                ::-webkit-scrollbar-thumb:hover {
                    background: #64748b;
                }

                /* Responsive */
                @media (max-width: 1024px) {
                    .main-grid {
                        grid-template-columns: 1fr;
                    }

                    .sidebar {
                        position: static;
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    }
                }

                @media (max-width: 768px) {
                    .container {
                        padding: 16px;
                    }

                    .header {
                        padding: 24px 20px;
                    }

                    .exception-message {
                        font-size: 20px;
                    }

                    .tabs-nav {
                        padding: 0 16px;
                    }

                    .tab-btn {
                        padding: 12px 16px;
                        font-size: 13px;
                    }

                    .sidebar {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        </head>

        <body>
            <div class="container">
                <!-- Header -->
                <div class="header">
                    <div class="header-content">
                        <div class="brand">
                            <div class="brand-logo">P</div>
                            <div class="brand-text">
                                <h1>Plugs Framework</h1>
                                <p>Exception Handler</p>
                            </div>
                        </div>
                        <div class="exception-message"><?= htmlspecialchars($exception->getMessage()) ?></div>
                        <div class="exception-meta">
                            <span class="badge badge-error">❌ Exception Thrown</span>
                            <span class="badge badge-file"><?= htmlspecialchars(get_class($exception)) ?></span>
                            <span class="badge badge-file">📄 <?= htmlspecialchars(basename($file)) ?>:<?= $line ?></span>
                        </div>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="main-grid">
                    <!-- Sidebar -->
                    <div class="sidebar">
                        <!-- Environment Info -->
                        <div class="info-panel">
                            <div class="panel-header">
                                <span class="panel-icon">ℹ️</span>
                                <h3>Environment</h3>
                            </div>
                            <div class="panel-content">
                                <div class="info-row">
                                    <span class="info-label">PHP Version</span>
                                    <span class="info-value"><?= PHP_VERSION ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Environment</span>
                                    <span class="env-badge"><?= strtoupper($this->app->environment() ?? 'local') ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Memory Usage</span>
                                    <span class="info-value"><?= $this->formatBytes(memory_get_usage(true)) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Peak Memory</span>
                                    <span class="info-value"><?= $this->formatBytes(memory_get_peak_usage(true)) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Time</span>
                                    <span class="info-value"><?= date('H:i:s') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Code Context -->
                        <div class="info-panel">
                            <div class="panel-header">
                                <span class="panel-icon">📄</span>
                                <h3>Code Context</h3>
                            </div>
                            <div class="panel-content no-padding">
                                <div class="code-context-file"><?= htmlspecialchars(basename($file)) ?></div>
                                <div class="code-lines-mini">
                                    <?php
                                    if (is_readable($file)) {
                                        $lines = file($file);
                                        $start = max($line - 5 - 1, 0);
                                        $end = min($line + 5, count($lines));
                                        for ($i = $start; $i < $end; $i++) {
                                            $currentLine = $i + 1;
                                            $codeLine = htmlspecialchars(rtrim($lines[$i], "\r\n"));
                                            $isHighlight = $currentLine === $line;
                                            echo '<div class="code-line-mini' . ($isHighlight ? ' highlight' : '') . '">';
                                            echo '<span class="line-num-mini">' . $currentLine . '</span>';
                                            echo '<span class="line-code-mini">' . ($codeLine ?: ' ') . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Stack Trace Summary -->
                        <div class="info-panel">
                            <div class="panel-header">
                                <span class="panel-icon">📚</span>
                                <h3>Stack Trace (<?= count($trace) ?>)</h3>
                            </div>
                            <div class="panel-content no-padding">
                                <div class="stack-list">
                                    <?php foreach (array_slice($trace, 0, 10) as $index => $item): ?>
                                        <div class="stack-item" onclick="document.querySelector('.tab-btn').click(); setTimeout(() => document.querySelectorAll('.trace-expandable')[<?= $index ?>]?.scrollIntoView({behavior: 'smooth', block: 'center'}), 100)">
                                            <span class="stack-number"><?= $index ?></span>
                                            <div>
                                                <div class="stack-func">
                                                    <?= isset($item['class']) ? htmlspecialchars($item['class']) . '::' : '' ?>
                                                    <?= isset($item['function']) ? htmlspecialchars($item['function']) . '()' : '[main]' ?>
                                                </div>
                                                <div class="stack-loc">
                                                    <?= isset($item['file']) ? htmlspecialchars(basename($item['file'])) : '[internal]' ?>
                                                    <?= isset($item['line']) ? ':' . $item['line'] : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="main-content">
                        <div class="tabs-nav">
                            <button class="tab-btn active" onclick="switchTab(this, 'tab-trace')">
                                🔍 Stack Trace
                            </button>
                            <button class="tab-btn" onclick="switchTab(this, 'tab-code')">
                                💻 Source Code
                            </button>
                            <button class="tab-btn" onclick="switchTab(this, 'tab-request')">
                                🌐 Request
                            </button>
                            <button class="tab-btn" onclick="switchTab(this, 'tab-env')">
                                ⚙️ Environment
                            </button>
                        </div>

                        <!-- Stack Trace Tab -->
                        <div id="tab-trace" class="tab-content active">
                            <input type="text" class="search-box" placeholder="Search stack trace..." onkeyup="filterStack(this.value)">
                            <?php foreach ($trace as $index => $item): ?>
                                <div class="trace-expandable" data-search="<?= htmlspecialchars(($item['file'] ?? '') . ($item['function'] ?? '')) ?>">
                                    <div class="trace-header-expand" onclick="this.parentElement.classList.toggle('expanded')">
                                        <div>
                                            <div class="stack-func">
                                                #<?= $index ?>
                                                <?= isset($item['class']) ? htmlspecialchars($item['class']) . '::' : '' ?>
                                                <?= isset($item['function']) ? htmlspecialchars($item['function']) . '()' : '[main execution]' ?>
                                            </div>
                                            <div class="stack-loc">
                                                <?= isset($item['file']) ? htmlspecialchars($item['file']) : '[internal function]' ?>
                                                <?= isset($item['line']) ? ':' . $item['line'] : '' ?>
                                            </div>
                                        </div>
                                        <span class="toggle-icon">▶</span>
                                    </div>
                                    <div class="trace-details">
                                        <?php if (isset($item['file']) && isset($item['line']) && is_readable($item['file'])): ?>
                                            <div class="code-block">
                                                <div class="code-header">
                                                    <span class="code-title"><?= htmlspecialchars(basename($item['file'])) ?> (Line <?= $item['line'] ?>)</span>
                                                    <button class="copy-btn" onclick="copyCode(this, event)">📋 Copy</button>
                                                </div>
                                                <div class="code-body">
                                                    <?= $this->getCodeSnippet($item['file'], $item['line'], 3) ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Source Code Tab -->
                        <div id="tab-code" class="tab-content">
                            <div class="code-block">
                                <div class="code-header">
                                    <span class="code-title"><?= htmlspecialchars($file) ?> (Line <?= $line ?>)</span>
                                    <button class="copy-btn" onclick="copyCode(this, event)">📋 Copy</button>
                                </div>
                                <div class="code-body">
                                    <?= $codeSnippet ?>
                                </div>
                            </div>
                        </div>

                        <!-- Request Tab -->
                        <div id="tab-request" class="tab-content">
                            <?php foreach ($requestData as $section => $data): ?>
                                <?php if (!empty($data)): ?>
                                    <h3 class="section-title"><?= ucwords(str_replace('_', ' ', $section)) ?></h3>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 30%">Key</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data as $key => $value): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($key) ?></td>
                                                    <td><?= htmlspecialchars(is_array($value) ? json_encode($value) : (string)$value) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Environment Tab -->
                        <div id="tab-env" class="tab-content">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 35%">Variable</th>
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

            <script>
                function switchTab(btn, tabId) {
                    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
                    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
                    document.getElementById(tabId).classList.add('active');
                    btn.classList.add('active');
                }

                function filterStack(term) {
                    const items = document.querySelectorAll('.trace-expandable');
                    term = term.toLowerCase();
                    items.forEach(item => {
                        const searchText = item.getAttribute('data-search').toLowerCase();
                        item.style.display = searchText.includes(term) ? 'block' : 'none';
                    });
                }

                function copyCode(btn, event) {
                    event.stopPropagation();
                    const codeBlock = btn.closest('.code-block').querySelector('.code-body');
                    const text = codeBlock.textContent;

                    navigator.clipboard.writeText(text).then(() => {
                        btn.textContent = '✓ Copied!';
                        setTimeout(() => btn.textContent = '📋 Copy', 2000);
                    }).catch(() => {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        btn.textContent = '✓ Copied!';
                        setTimeout(() => btn.textContent = '📋 Copy', 2000);
                    });
                }

                // Auto-expand first trace item
                document.addEventListener('DOMContentLoaded', function() {
                    const firstTrace = document.querySelector('.trace-expandable');
                    if (firstTrace) firstTrace.classList.add('expanded');
                });

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        document.querySelector('.search-box')?.focus();
                    }
                });
            </script>
        </body>

        </html>
<?php
        $html = ob_get_clean();
        return new Response($html, 500, ['Content-Type' => 'text/html']);
    }

    protected function renderProductionResponse(Throwable $exception): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        .container {
            text-align: center;
            max-width: 600px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 32px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        p {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn {
            background: white;
            color: #667eea;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">500</div>
        <h1>Something went wrong</h1>
        <p>We're sorry, but something went wrong on our end. Our team has been notified and is working to fix the issue.</p>
        <a href="/" class="btn">Go to Homepage</a>
    </div>
</body>
</html>
HTML;
        return new Response($html, 500, ['Content-Type' => 'text/html']);
    }

    public function report(Throwable $exception): void
    {
        error_log(sprintf(
            "[%s] %s: %s in %s:%s\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));
    }

    protected function getCodeSnippet(string $file, int $line, int $padding = 5): string
    {
        if (!is_readable($file)) {
            return '<div class="empty-state"><div class="empty-state-icon">📄</div><p>File Not Readable</p></div>';
        }

        $lines = file($file);
        $start = max($line - $padding - 1, 0);
        $end = min($line + $padding, count($lines));
        $snippet = '';

        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $codeLine = htmlspecialchars(rtrim($lines[$i], "\r\n"));
            $isHighlight = $currentLine === $line;

            $snippet .= sprintf(
                '<div class="code-line %s"><div class="line-num">%d</div><div class="line-code">%s</div></div>',
                $isHighlight ? 'highlight' : '',
                $currentLine,
                $codeLine ?: ' '
            );
        }

        return $snippet;
    }

    protected function getRequestData(Request $request): array
    {
        return [
            'headers' => $request->headers() ?? [],
            'get_parameters' => $_GET ?? [],
            'post_data' => $_POST ?? [],
            'cookies' => $_COOKIE ?? [],
            'server_info' => array_filter($_SERVER, function ($key) {
                return in_array($key, [
                    'REQUEST_METHOD',
                    'REQUEST_URI',
                    'HTTP_HOST',
                    'HTTP_USER_AGENT',
                    'HTTP_ACCEPT',
                    'REMOTE_ADDR',
                    'REQUEST_TIME',
                    'SERVER_PORT',
                    'QUERY_STRING',
                    'CONTENT_TYPE',
                    'CONTENT_LENGTH'
                ]);
            }, ARRAY_FILTER_USE_KEY)
        ];
    }

    protected function getEnvironmentData(): array
    {
        return [
            'PHP Version' => PHP_VERSION,
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Operating System' => PHP_OS,
            'Memory Limit' => ini_get('memory_limit'),
            'Memory Usage' => $this->formatBytes(memory_get_usage(true)),
            'Peak Memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'Max Execution Time' => ini_get('max_execution_time') . 's',
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Timezone' => date_default_timezone_get(),
            'Current Time' => date('Y-m-d H:i:s T'),
            'Environment' => $this->app->environment() ?? 'unknown',
            'Extensions Loaded' => count(get_loaded_extensions()),
        ];
    }

    protected function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
