<?php

declare(strict_types=1);

namespace Plugs\Dumper;

use ReflectionObject;
use ReflectionProperty;
use Error;
use Exception;
use Throwable;

class Dumper
{
    // Configuration defaults
    private static int $maxDepth = 5;
    private static int $maxArrayItems = 20;
    private static int $maxObjectProps = 20;
    private static int $maxStringLength = 500;
    private static bool $showCallerContext = true;
    private static bool $showMemoryUsage = true;
    private static bool $darkMode = false;
    private static bool $showTrace = true;

    // Internal state
    private static bool $cssLoaded = false;
    private static bool $jsLoaded = false;
    private static array $processedObjects = [];
    private static array $allowedIPs = ['127.0.0.1', '::1'];
    private static bool $initialized = false;

    // Constants
    private const MAX_DEPTH_LIMIT = 10;
    private const MAX_ITEMS_LIMIT = 100;
    private const MAX_STRING_LIMIT = 2000;

    /**
     * Initialize the dumper with custom settings
     */
    public static function init(array $config = []): void
    {
        if (self::$initialized) {
            return;
        }

        self::$maxDepth = min($config['maxDepth'] ?? self::$maxDepth, self::MAX_DEPTH_LIMIT);
        self::$maxArrayItems = min($config['maxArrayItems'] ?? self::$maxArrayItems, self::MAX_ITEMS_LIMIT);
        self::$maxObjectProps = min($config['maxObjectProps'] ?? self::$maxObjectProps, self::MAX_ITEMS_LIMIT);
        self::$maxStringLength = min($config['maxStringLength'] ?? self::$maxStringLength, self::MAX_STRING_LIMIT);
        self::$showCallerContext = $config['showCallerContext'] ?? self::$showCallerContext;
        self::$showMemoryUsage = $config['showMemoryUsage'] ?? self::$showMemoryUsage;
        self::$darkMode = $config['darkMode'] ?? self::$darkMode;
        self::$showTrace = $config['showTrace'] ?? self::$showTrace;
        self::$allowedIPs = array_merge(self::$allowedIPs, $config['allowedIPs'] ?? []);

        self::$initialized = true;
    }

    /**
     * Dump variables with full context
     */
    public static function dump(...$vars): void
    {
        if (!self::isDebuggingAllowed()) {
            return;
        }

        self::$processedObjects = [];
        ob_start();

        $groupId = uniqid('dump-');
        $backtrace = self::getRelevantBacktrace();
        $callerContext = self::$showCallerContext ? self::getCallerContext($backtrace) : null;

        self::loadAssets($groupId);

        echo "<div class='plugs-dump-wrapper' id='{$groupId}'>";
        echo self::getFrameworkHeader();
        echo self::getGroupHeader($groupId, $backtrace, $callerContext);

        echo "<div class='dump-variables-grid'>";
        foreach ($vars as $index => $var) {
            self::renderVariable($var, $groupId, $index + 1);
        }
        echo "</div>";

        echo "</div>";

        ob_end_flush();
    }

    /**
     * Dump and die
     */
    public static function dd(...$vars): void
    {
        self::dump(...$vars);
        die(1);
    }

    /**
     * Quick dump for large data structures
     */
    public static function quickDump(...$vars): void
    {
        if (!self::isDebuggingAllowed()) {
            return;
        }

        ob_start();

        $backtrace = self::getRelevantBacktrace();
        $location = basename($backtrace['file'] ?? 'unknown') . ':' . ($backtrace['line'] ?? 'unknown');

        echo "<div class='plugs-quick-dump'>";
        echo "<div class='quick-dump-header'>";
        echo "<span class='quick-dump-icon'>⚡</span>";
        echo "<span>Quick Dump @ {$location}</span>";
        echo "</div>";

        echo "<pre class='quick-dump-content'>";
        foreach ($vars as $var) {
            echo htmlspecialchars(print_r($var, true)), "\n---\n";
        }
        echo "</pre></div>";

        ob_end_flush();
    }

    /**
     * Log dump to file
     */
    public static function log(...$vars): void
    {
        $backtrace = self::getRelevantBacktrace();
        $location = basename($backtrace['file'] ?? 'unknown') . ':' . ($backtrace['line'] ?? 'unknown');
        $timestamp = date('Y-m-d H:i:s');

        $logContent = "[$timestamp] $location\n";
        foreach ($vars as $index => $var) {
            $logContent .= "Variable #" . ($index + 1) . ":\n";
            $logContent .= print_r($var, true) . "\n\n";
        }

        $logFile = sys_get_temp_dir() . '/plugs-dump.log';
        error_log($logContent, 3, $logFile);
    }

    /**
     * Check if debugging is allowed
     */
    private static function isDebuggingAllowed(): bool
    {
        $env = strtolower($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production');
        if (!in_array($env, ['local', 'development', 'dev', 'testing', 'staging'])) {
            return false;
        }

        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!in_array($clientIP, self::$allowedIPs) && !in_array('*', self::$allowedIPs)) {
            return false;
        }

        if (headers_sent()) {
            return false;
        }

        return true;
    }

    /**
     * Get the most relevant backtrace entry
     */
    private static function getRelevantBacktrace(): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($backtrace as $entry) {
            if (!isset($entry['class']) || strpos($entry['class'], 'Plugs\\Dumper\\') !== 0) {
                return $entry;
            }
        }

        return $backtrace[0] ?? [];
    }

    /**
     * Get context around the dump call
     */
    private static function getCallerContext(array $backtrace): ?string
    {
        if (!isset($backtrace['file']) || !is_readable($backtrace['file'])) {
            return null;
        }

        $line = $backtrace['line'] ?? 0;
        $fileContent = file($backtrace['file']);
        $start = max(0, $line - 5);
        $end = min(count($fileContent), $line + 5);
        $context = [];

        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $content = htmlspecialchars($fileContent[$i] ?? '', ENT_QUOTES, 'UTF-8');
            $highlight = ($currentLine === $line) ? 'highlight' : '';
            $context[] = sprintf(
                '<div class="code-line %s"><span class="line-number">%d</span><span class="line-content">%s</span></div>',
                $highlight,
                $currentLine,
                $content
            );
        }

        return sprintf(
            '<div class="caller-context"><div class="context-header">Code Context</div><div class="code-lines">%s</div></div>',
            implode("\n", $context)
        );
    }

    /**
     * Load required CSS and JavaScript
     */
    private static function loadAssets(string $groupId): void
    {
        if (!self::$cssLoaded) {
            echo self::getCss();
            self::$cssLoaded = true;
        }

        if (!self::$jsLoaded) {
            echo self::getJavaScript($groupId);
            self::$jsLoaded = true;
        }
    }

    private static function getCss(): string
    {
        return <<<'CSS'
        <style>
        * { box-sizing: border-box; }
        
        .plugs-dump-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 24px auto;
            max-width: 1400px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }

        .plugs-framework-header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #b91c1c;
        }

        .plugs-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .plugs-logo {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            color: white;
            letter-spacing: -1px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .plugs-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .plugs-subtitle {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
            margin: 2px 0 0 0;
            font-weight: 400;
        }

        .plugs-info-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dump-location-header {
            background: #f9fafb;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 12px;
        }

        .dump-file-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .dump-file-info strong {
            color: #111827;
            font-weight: 600;
        }

        .dump-controls {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dump-btn {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            color: #374151;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dump-btn:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }

        .dump-btn.primary {
            background: #ef4444;
            color: white;
            border-color: #dc2626;
        }

        .dump-btn.primary:hover {
            background: #dc2626;
        }

        .dump-variables-grid {
            padding: 24px;
            background: #f9fafb;
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }

        .dump-variable-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .dump-variable-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .dump-variable-header {
            background: #374151;
            color: white;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dump-variable-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .variable-number {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            min-width: 28px;
            text-align: center;
        }

        .dump-type-badge {
            background: #3b82f6;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .dump-class-badge {
            background: #10b981;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            color: white;
        }

        .dump-variable-actions {
            display: flex;
            gap: 6px;
        }

        .dump-toggle, .dump-copy {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dump-toggle:hover, .dump-copy:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .dump-content {
            background: #1f2937;
            color: #e5e7eb;
            padding: 20px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            white-space: pre;
            max-height: 500px;
            overflow-y: auto;
        }

        .dump-content.collapsed {
            display: none;
        }

        .caller-context {
            background: #f3f4f6;
            border-top: 1px solid #e5e7eb;
        }

        .context-header {
            padding: 12px 24px;
            background: #e5e7eb;
            font-weight: 600;
            font-size: 12px;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .code-lines {
            padding: 16px 0;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
            font-size: 13px;
            overflow-x: auto;
        }

        .code-line {
            display: flex;
            padding: 2px 0;
        }

        .code-line.highlight {
            background: #fef3c7;
        }

        .line-number {
            color: #9ca3af;
            padding: 0 16px 0 24px;
            text-align: right;
            user-select: none;
            min-width: 60px;
        }

        .line-content {
            flex: 1;
            padding-right: 24px;
            color: #374151;
        }

        .dump-content::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .dump-content::-webkit-scrollbar-track {
            background: #111827;
        }

        .dump-content::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 4px;
        }

        .dump-content::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        /* Syntax highlighting */
        .dump-string { color: #34d399; }
        .dump-number { color: #a78bfa; }
        .dump-boolean { color: #f87171; }
        .dump-null { color: #9ca3af; font-style: italic; }
        .dump-array-key { color: #fbbf24; }
        .dump-object-property { color: #60a5fa; }
        .dump-visibility { color: #a78bfa; font-style: italic; }
        .dump-resource { color: #fb923c; }
        .dump-class-name { color: #34d399; font-weight: bold; }
        .dump-truncated { color: #9ca3af; font-style: italic; }
        .dump-circular { color: #f87171; font-style: italic; }

        /* Quick dump styles */
        .plugs-quick-dump {
            margin: 20px auto;
            max-width: 1200px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .quick-dump-header {
            background: #374151;
            color: white;
            padding: 12px 16px;
            font-family: monospace;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-dump-icon {
            font-size: 16px;
        }

        .quick-dump-content {
            background: #1f2937;
            color: #e5e7eb;
            padding: 20px;
            margin: 0;
            font-size: 13px;
            max-height: 400px;
            overflow: auto;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .plugs-dump-wrapper {
                margin: 12px;
                border-radius: 8px;
            }

            .plugs-framework-header {
                padding: 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .dump-variables-grid {
                grid-template-columns: 1fr;
                padding: 16px;
                gap: 16px;
            }

            .dump-location-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 16px;
            }

            .dump-controls {
                width: 100%;
            }

            .dump-btn {
                flex: 1;
                justify-content: center;
            }

            .plugs-logo {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .plugs-title {
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .dump-content {
                font-size: 11px;
                padding: 12px;
            }

            .code-lines {
                font-size: 11px;
            }

            .line-number {
                padding: 0 8px 0 12px;
                min-width: 40px;
            }

            .line-content {
                padding-right: 12px;
            }
        }
        </style>
        CSS;
    }

    private static function getJavaScript(string $groupId): string
    {
        return <<<'JS'
        <script>
        (function() {
            'use strict';

            document.addEventListener('DOMContentLoaded', function() {
                // Toggle individual variables
                document.querySelectorAll('.dump-toggle').forEach(button => {
                    button.addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');
                        const target = document.getElementById(targetId);
                        const isCollapsed = target.classList.contains('collapsed');

                        target.classList.toggle('collapsed');
                        this.textContent = isCollapsed ? '▼' : '▶';
                    });
                });

                // Copy functionality
                document.querySelectorAll('.dump-copy').forEach(button => {
                    button.addEventListener('click', async function() {
                        const card = this.closest('.dump-variable-card');
                        const content = card.querySelector('.dump-content').textContent;
                        const originalText = this.textContent;

                        try {
                            if (navigator.clipboard) {
                                await navigator.clipboard.writeText(content);
                            } else {
                                const textArea = document.createElement('textarea');
                                textArea.value = content;
                                textArea.style.position = 'fixed';
                                textArea.style.left = '-9999px';
                                document.body.appendChild(textArea);
                                textArea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textArea);
                            }

                            this.textContent = '✓ Copied';
                            setTimeout(() => this.textContent = originalText, 2000);
                        } catch (err) {
                            this.textContent = '✗ Failed';
                            setTimeout(() => this.textContent = originalText, 2000);
                        }
                    });
                });

                // Expand/Collapse all
                document.querySelectorAll('.dump-expand-all').forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-group');
                        const shouldExpand = this.textContent.includes('Expand');

                        document.querySelectorAll(`#${groupId} .dump-toggle`).forEach(toggle => {
                            const targetId = toggle.getAttribute('data-target');
                            const target = document.getElementById(targetId);

                            if (shouldExpand) {
                                target.classList.remove('collapsed');
                                toggle.textContent = '▼';
                            } else {
                                target.classList.add('collapsed');
                                toggle.textContent = '▶';
                            }
                        });

                        this.textContent = shouldExpand ? 'Collapse All' : 'Expand All';
                    });
                });

                // Export to JSON
                document.querySelectorAll('.dump-export-json').forEach(button => {
                    button.addEventListener('click', function() {
                        const groupId = this.getAttribute('data-group');
                        const cards = document.querySelectorAll(`#${groupId} .dump-variable-card`);
                        const data = [];

                        cards.forEach(card => {
                            const content = card.querySelector('.dump-content').textContent;
                            data.push(content);
                        });

                        const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `dump-${Date.now()}.json`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    });
                });
            });
        })();
        </script>
        JS;
    }

    private static function getFrameworkHeader(): string
    {
        $memoryUsage = self::$showMemoryUsage ? ' • ' . self::formatMemory(memory_get_usage(true)) : '';

        return <<<HTML
        <div class='plugs-framework-header'>
            <div class='plugs-brand'>
                <div class='plugs-logo'>
                    P
                </div>
                <div>
                    <h2 class='plugs-title'>Plugs Framework</h2>
                    <p class='plugs-subtitle'>Debug Data Dumper</p>
                </div>
            </div>
            <div class='plugs-info-badge'>
                Development Mode{$memoryUsage}
            </div>
        </div>
        HTML;
    }

    private static function getGroupHeader(string $groupId, array $backtrace, ?string $callerContext = null): string
    {
        if (!is_array($backtrace)) {
            $backtrace = [];
        }

        $file = isset($backtrace['file']) && is_string($backtrace['file'])
            ? htmlspecialchars(basename($backtrace['file']), ENT_QUOTES, 'UTF-8')
            : 'unknown';
        $line = htmlspecialchars((string)($backtrace['line'] ?? 'unknown'), ENT_QUOTES, 'UTF-8');
        $timestamp = date('H:i:s');

        $controls = implode('', [
            '<button class="dump-btn dump-expand-all" data-group="' . $groupId . '">Expand All</button>',
            '<button class="dump-btn dump-export-json" data-group="' . $groupId . '">📥 Export</button>',
        ]);

        $header = <<<HTML
        <div class='dump-location-header'>
            <div class='dump-file-info'>
                <strong>{$file}</strong>
                <span>•</span>
                <span>Line {$line}</span>
                <span>•</span>
                <span>{$timestamp}</span>
            </div>
            <div class='dump-controls'>
                {$controls}
            </div>
        </div>
        HTML;

        if ($callerContext) {
            $header .= $callerContext;
        }

        return $header;
    }

    private static function renderVariable($var, string $groupId, int $varNumber): void
    {
        $varId = uniqid('var-');
        $varType = gettype($var);
        $classInfo = is_object($var) ? get_class($var) : '';
        $shortClass = $classInfo ? basename(str_replace('\\', '/', $classInfo)) : '';

        echo "<div class='dump-variable-card'>";
        echo "<div class='dump-variable-header'>";
        echo "<div class='dump-variable-info'>";
        echo "<span class='variable-number'>{$varNumber}</span>";
        echo "<span class='dump-type-badge'>{$varType}</span>";

        if ($shortClass) {
            echo "<span class='dump-class-badge'>{$shortClass}</span>";
        }

        echo "</div>";
        echo "<div class='dump-variable-actions'>";
        echo "<button class='dump-toggle' data-target='{$varId}'>▼</button>";
        echo "<button class='dump-copy'>Copy</button>";
        echo "</div>";
        echo "</div>";

        echo "<div id='{$varId}' class='dump-content'>";
        echo self::formatVariable($var);
        echo "</div>";
        echo "</div>";
    }

    private static function formatVariable($var, int $depth = 0, string $path = ''): string
    {
        if ($depth > self::$maxDepth) {
            return '<span class="dump-truncated">[Max depth reached]</span>';
        }

        $spaces = str_repeat('  ', $depth);
        $type = gettype($var);

        switch ($type) {
            case 'NULL':
                return '<span class="dump-null">null</span>';

            case 'boolean':
                return '<span class="dump-boolean">' . ($var ? 'true' : 'false') . '</span>';

            case 'integer':
            case 'double':
                return '<span class="dump-number">' . $var . '</span>';

            case 'string':
                return self::formatString($var);

            case 'array':
                return self::formatArray($var, $depth, $spaces, $path);

            case 'object':
                return self::formatObject($var, $depth, $spaces, $path);

            case 'resource':
            case 'resource (closed)':
                return '<span class="dump-resource">' . $type . '(' . get_resource_type($var) . ')</span>';

            default:
                return '<span class="dump-null">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    }

    private static function formatString(string $var): string
    {
        $length = strlen($var);
        $isBinary = !mb_check_encoding($var, 'UTF-8');

        if ($isBinary) {
            return '<span class="dump-string">[binary data]</span> <span class="dump-truncated">(' . $length . ' bytes)</span>';
        }

        if ($length > self::$maxStringLength) {
            $preview = substr($var, 0, self::$maxStringLength);
            return '<span class="dump-string">"' . htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') .
                '..."</span> <span class="dump-truncated">(' . $length . ' chars)</span>';
        }

        return '<span class="dump-string">"' . htmlspecialchars($var, ENT_QUOTES, 'UTF-8') . '"</span>';
    }

    private static function formatArray(array $var, int $depth, string $spaces, string $path): string
    {
        $count = count($var);

        if ($count === 0) {
            return '<span class="dump-array-key">array(0)</span> []';
        }

        $output = "<span class=\"dump-array-key\">array({$count})</span> [\n";
        $itemCount = 0;

        foreach ($var as $key => $value) {
            if ($itemCount >= self::$maxArrayItems) {
                $remaining = $count - self::$maxArrayItems;
                $output .= "{$spaces}  <span class=\"dump-truncated\">... {$remaining} more items</span>\n";
                break;
            }

            $formattedKey = is_int($key)
                ? "<span class=\"dump-number\">{$key}</span>"
                : "<span class=\"dump-string\">\"" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "\"</span>";

            $newPath = $path ? "{$path}[{$key}]" : "[{$key}]";

            $output .= sprintf(
                "%s  %s => %s,\n",
                $spaces,
                $formattedKey,
                self::formatVariable($value, $depth + 1, $newPath)
            );

            $itemCount++;
        }

        return $output . $spaces . ']';
    }

    private static function formatObject($var, int $depth, string $spaces, string $path): string
    {
        $className = get_class($var);
        $objectId = spl_object_id($var);
        $shortClassName = basename(str_replace('\\', '/', $className));

        // Check for circular reference
        if (isset(self::$processedObjects[$objectId])) {
            return '<span class="dump-circular">*CIRCULAR REFERENCE* ' . $shortClassName . '</span>';
        }

        self::$processedObjects[$objectId] = true;

        try {
            $reflection = new ReflectionObject($var);
            $properties = $reflection->getProperties();

            $output = "<span class=\"dump-class-name\">{$shortClassName}</span> {\n";
            $propCount = 0;
            $totalProps = count($properties);
            $maxProps = min(self::$maxObjectProps, $totalProps);

            foreach ($properties as $property) {
                if ($propCount >= $maxProps) {
                    $remaining = $totalProps - $maxProps;
                    $output .= "{$spaces}  <span class=\"dump-truncated\">... {$remaining} more properties</span>\n";
                    break;
                }

                $property->setAccessible(true);
                $modifiers = self::getPropertyModifiers($property);
                $propertyName = $property->getName();
                $newPath = $path ? "{$path}->{$propertyName}" : "->{$propertyName}";

                try {
                    $value = $property->getValue($var);
                    $formattedValue = self::formatVariable($value, $depth + 1, $newPath);
                } catch (Throwable $e) {
                    $formattedValue = '<span class="dump-null">[uninitialized]</span>';
                }

                $output .= sprintf(
                    "%s  [<span class=\"dump-visibility\">%s</span>] <span class=\"dump-object-property\">%s</span> => %s,\n",
                    $spaces,
                    $modifiers,
                    $propertyName,
                    $formattedValue
                );

                $propCount++;
            }

            $result = $output . $spaces . '}';
        } catch (Throwable $e) {
            $result = '<span class="dump-class-name">' . $shortClassName . '</span> <span class="dump-null">[Error: ' .
                htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ']</span>';
        } finally {
            unset(self::$processedObjects[$objectId]);
        }

        return $result;
    }

    private static function getPropertyModifiers(ReflectionProperty $property): string
    {
        $modifiers = [];

        if ($property->isPublic()) {
            $modifiers[] = 'public';
        } elseif ($property->isProtected()) {
            $modifiers[] = 'protected';
        } elseif ($property->isPrivate()) {
            $modifiers[] = 'private';
        }

        if ($property->isStatic()) {
            $modifiers[] = 'static';
        }

        if (method_exists($property, 'isReadOnly') && $property->isReadOnly()) {
            $modifiers[] = 'readonly';
        }

        return implode(' ', $modifiers);
    }

    private static function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return sprintf('%.2f %s', $bytes, $units[$index]);
    }

    /**
     * Configuration setters with validation
     */
    public static function setMaxDepth(int $depth): void
    {
        self::$maxDepth = max(1, min(self::MAX_DEPTH_LIMIT, $depth));
    }

    public static function setMaxArrayItems(int $items): void
    {
        self::$maxArrayItems = max(1, min(self::MAX_ITEMS_LIMIT, $items));
    }

    public static function setMaxObjectProps(int $props): void
    {
        self::$maxObjectProps = max(1, min(self::MAX_ITEMS_LIMIT, $props));
    }

    public static function setMaxStringLength(int $length): void
    {
        self::$maxStringLength = max(10, min(self::MAX_STRING_LIMIT, $length));
    }

    public static function setDarkMode(bool $enabled): void
    {
        self::$darkMode = $enabled;
    }

    public static function setShowCallerContext(bool $enabled): void
    {
        self::$showCallerContext = $enabled;
    }

    public static function setShowMemoryUsage(bool $enabled): void
    {
        self::$showMemoryUsage = $enabled;
    }

    public static function setShowTrace(bool $enabled): void
    {
        self::$showTrace = $enabled;
    }

    public static function setAllowedIPs(array $ips): void
    {
        self::$allowedIPs = $ips;
    }

    public static function addAllowedIP(string $ip): void
    {
        if (!in_array($ip, self::$allowedIPs)) {
            self::$allowedIPs[] = $ip;
        }
    }

    public static function removeAllowedIP(string $ip): void
    {
        self::$allowedIPs = array_values(array_diff(self::$allowedIPs, [$ip]));
    }

    public static function clearAllowedIPs(): void
    {
        self::$allowedIPs = ['127.0.0.1', '::1'];
    }

    /**
     * Get current configuration
     */
    public static function getConfig(): array
    {
        return [
            'maxDepth' => self::$maxDepth,
            'maxArrayItems' => self::$maxArrayItems,
            'maxObjectProps' => self::$maxObjectProps,
            'maxStringLength' => self::$maxStringLength,
            'showCallerContext' => self::$showCallerContext,
            'showMemoryUsage' => self::$showMemoryUsage,
            'darkMode' => self::$darkMode,
            'showTrace' => self::$showTrace,
            'allowedIPs' => self::$allowedIPs,
        ];
    }

    /**
     * Reset configuration to defaults
     */
    public static function resetConfig(): void
    {
        self::$maxDepth = 5;
        self::$maxArrayItems = 20;
        self::$maxObjectProps = 20;
        self::$maxStringLength = 500;
        self::$showCallerContext = true;
        self::$showMemoryUsage = true;
        self::$darkMode = false;
        self::$showTrace = true;
        self::$allowedIPs = ['127.0.0.1', '::1'];
        self::$initialized = false;
    }
}
