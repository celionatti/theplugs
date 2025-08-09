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

        // Apply configuration
        self::$maxDepth = min($config['maxDepth'] ?? self::$maxDepth, self::MAX_DEPTH_LIMIT);
        self::$maxArrayItems = min($config['maxArrayItems'] ?? self::$maxArrayItems, self::MAX_ITEMS_LIMIT);
        self::$maxObjectProps = min($config['maxObjectProps'] ?? self::$maxObjectProps, self::MAX_ITEMS_LIMIT);
        self::$maxStringLength = min($config['maxStringLength'] ?? self::$maxStringLength, self::MAX_STRING_LIMIT);
        self::$showCallerContext = $config['showCallerContext'] ?? self::$showCallerContext;
        self::$showMemoryUsage = $config['showMemoryUsage'] ?? self::$showMemoryUsage;
        self::$darkMode = $config['darkMode'] ?? self::$darkMode;
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

        // Reset processed objects for each dump call
        self::$processedObjects = [];

        // Capture output to prevent breaking the layout
        ob_start();

        $groupId = uniqid('dump-');
        $backtrace = self::getRelevantBacktrace();
        $callerContext = self::$showCallerContext ? self::getCallerContext($backtrace) : null;

        // Load assets only once
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

        // Flush the output buffer
        ob_end_flush();
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

        echo "<div class='plugs-quick-dump' style='margin:20px 0;'>";
        echo "<div style='background:#2d3748;color:white;padding:8px 12px;font-family:monospace;font-size:12px;'>";
        echo "Quick Dump @ {$location}";
        echo "</div>";

        echo "<pre style='background:#f8fafc;padding:12px;border:1px solid #e2e8f0;margin:0;font-size:12px;max-height:300px;overflow:auto;'>";
        foreach ($vars as $var) {
            echo htmlspecialchars(print_r($var, true)), "\n---\n";
        }
        echo "</pre></div>";

        ob_end_flush();
    }

    /**
     * Check if debugging is allowed
     */
    private static function isDebuggingAllowed(): bool
    {
        // Check environment
        $env = strtolower($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production');
        if (!in_array($env, ['local', 'development', 'dev', 'testing', 'staging'])) {
            return false;
        }

        // Check IP restriction
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!in_array($clientIP, self::$allowedIPs) && !in_array('*', self::$allowedIPs)) {
            return false;
        }

        // Check if headers already sent
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

        // Skip internal framework calls
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
            $highlight = ($currentLine === $line) ? 'style="background:#fff3bf;"' : '';
            $context[] = sprintf(
                '<tr %s><td class="line-number">%d</td><td class="line-content">%s</td></tr>',
                $highlight,
                $currentLine,
                $content
            );
        }

        return sprintf(
            '<div class="caller-context"><table>%s</table></div>',
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
        $darkModeStyles = self::$darkMode ? <<<'CSS'
        .plugs-dump-wrapper {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
            color: #e2e8f0 !important;
        }
        .dump-variable-card {
            background: #2d3748 !important;
            border-color: #4a5568 !important;
        }
        .dump-content {
            background: #1a202c !important;
            color: #e2e8f0 !important;
        }
        .dump-location-header {
            background: rgba(0, 0, 0, 0.5) !important;
            color: #e2e8f0 !important;
            border-color: #4a5568 !important;
        }
        .dump-file-info {
            color: #cbd5e0 !important;
        }
        .dump-content::-webkit-scrollbar-track {
            background: #2d3748 !important;
        }
        .dump-content::-webkit-scrollbar-thumb {
            background: #4a5568 !important;
        }
        CSS : '';

        return <<<CSS
        <style>
        .plugs-dump-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 20px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            max-width: 100%;
        }

        .plugs-framework-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .plugs-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .plugs-logo {
            width: 28px;
            height: 28px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: white;
        }

        .plugs-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
        }

        .plugs-subtitle {
            font-size: 11px;
            color: #a0aec0;
            margin: 0;
            font-weight: 400;
        }

        .plugs-info-badge {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 11px;
            color: #e2e8f0;
        }

        .dump-location-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .dump-file-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #4a5568;
            font-weight: 500;
        }

        .dump-controls {
            display: flex;
            gap: 8px;
        }

        .dump-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .dump-btn:hover {
            transform: translateY(-1px);
        }

        .dump-btn.secondary {
            background: #f7fafc;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }

        .dump-variables-grid {
            padding: 20px;
            background: #f8fafc;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        }

        .dump-variable-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .dump-variable-card:hover {
            transform: translateY(-1px);
        }

        .dump-variable-header {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            color: white;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dump-variable-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .variable-number {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        .dump-type-badge {
            background: #4299e1;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            color: white;
        }

        .dump-class-badge {
            background: #38a169;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            color: white;
        }

        .dump-variable-actions {
            display: flex;
            gap: 4px;
        }

        .dump-toggle, .dump-copy {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .dump-toggle:hover, .dump-copy:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .dump-content {
            background: #fafafa;
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            overflow-x: auto;
            white-space: pre;
            max-height: 400px;
            overflow-y: auto;
        }

        .dump-content.collapsed {
            display: none;
        }

        .caller-context {
            background: #f8f9fa;
            padding: 10px;
            border-top: 1px solid #e9ecef;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow: auto;
        }

        .caller-context table {
            width: 100%;
            border-collapse: collapse;
        }

        .caller-context .line-number {
            color: #6c757d;
            padding-right: 10px;
            text-align: right;
            user-select: none;
        }

        .caller-context .line-content {
            white-space: pre;
        }

        .dump-content::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .dump-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .dump-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        /* Syntax highlighting */
        .dump-string { color: #22863a; }
        .dump-number { color: #6f42c1; }
        .dump-boolean { color: #d73a49; }
        .dump-null { color: #6a737d; font-style: italic; }
        .dump-array-key { color: #e36209; }
        .dump-object-property { color: #005cc5; }
        .dump-visibility { color: #6f42c1; font-style: italic; }
        .dump-resource { color: #b08800; }
        .dump-class-name { color: #22863a; font-weight: bold; }
        .dump-truncated { color: #6a737d; font-style: italic; }
        .dump-circular { color: #d73a49; font-style: italic; }

        /* Dark mode syntax highlighting */
        .dark-mode .dump-string { color: #9ecbff; }
        .dark-mode .dump-number { color: #b392f0; }
        .dark-mode .dump-boolean { color: #ff8383; }
        .dark-mode .dump-null { color: #8b949e; }
        .dark-mode .dump-array-key { color: #ffab70; }
        .dark-mode .dump-object-property { color: #79b8ff; }
        .dark-mode .dump-visibility { color: #b392f0; }
        .dark-mode .dump-resource { color: #e3b341; }
        .dark-mode .dump-class-name { color: #7ee787; }
        .dark-mode .dump-truncated { color: #8b949e; }
        .dark-mode .dump-circular { color: #ff8383; }

        /* Responsive */
        @media (max-width: 768px) {
            .dump-variables-grid {
                grid-template-columns: 1fr;
                padding: 16px;
            }

            .dump-location-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .dump-controls {
                width: 100%;
                justify-content: flex-end;
            }
        }

        {$darkModeStyles}
        </style>
        CSS;
    }

    private static function getJavaScript(string $groupId): string
    {
        return <<<JS
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle individual variables
            document.querySelectorAll('.dump-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const target = document.getElementById(targetId);
                    const isCollapsed = target.classList.contains('collapsed');

                    if (isCollapsed) {
                        target.classList.remove('collapsed');
                        this.textContent = '▼';
                    } else {
                        target.classList.add('collapsed');
                        this.textContent = '▶';
                    }
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
                            // Fallback for older browsers
                            const textArea = document.createElement('textarea');
                            textArea.value = content;
                            document.body.appendChild(textArea);
                            textArea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                        }

                        this.textContent = '✓ Copied!';
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

                    document.querySelectorAll(`#\${groupId} .dump-toggle`).forEach(toggle => {
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
                    const cards = document.querySelectorAll(`#\${groupId} .dump-variable-card`);
                    const data = [];

                    cards.forEach(card => {
                        const content = card.querySelector('.dump-content').textContent;
                        data.push(content);
                    });

                    const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `dump-\${groupId}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            });

            // Toggle dark mode
            document.querySelectorAll('.dump-toggle-dark-mode').forEach(button => {
                button.addEventListener('click', function() {
                    const wrapper = document.querySelector('.plugs-dump-wrapper');
                    wrapper.classList.toggle('dark-mode');
                    this.textContent = wrapper.classList.contains('dark-mode') ? '☀️ Light' : '🌙 Dark';
                });
            });
        });
        </script>
        JS;
    }

    private static function getFrameworkHeader(): string
    {
        $memoryUsage = self::$showMemoryUsage ? self::formatMemory(memory_get_usage(true)) : '';

        return <<<HTML
        <div class='plugs-framework-header'>
            <div class='plugs-brand'>
                <div class='plugs-logo'>🔌</div>
                <div>
                    <h2 class='plugs-title'>Plugs Framework</h2>
                    <p class='plugs-subtitle'>Debug Data Dumper</p>
                </div>
            </div>
            <div class='plugs-info-badge'>
                Development Mode {$memoryUsage}
            </div>
        </div>
        HTML;
    }

    private static function getGroupHeader(string $groupId, array $backtrace, ?string $callerContext = null): string
    {
        if (!is_array($backtrace)) {
            $backtrace = [];
        }

        $file = isset($backtrace['file']) && is_string($backtrace['file']) ? htmlspecialchars(basename($backtrace['file']), ENT_QUOTES, 'UTF-8') : 'unknown';
        $line = htmlspecialchars((string)$backtrace['line'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
        $timestamp = date('H:i:s');

        $controls = implode('', [
            '<button class="dump-btn dump-expand-all" data-group="' . $groupId . '">Expand All</button>',
            '<button class="dump-btn dump-export-json" data-group="' . $groupId . '">Export JSON</button>',
            '<button class="dump-btn dump-toggle-dark-mode" data-group="' . $groupId . '">' . (self::$darkMode ? '☀️ Light' : '🌙 Dark') . '</button>'
        ]);

        $header = <<<HTML
    <div class='dump-location-header'>
        <div class='dump-file-info'>
            <strong>{$file}</strong>
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
                $output .= "{$spaces}  <span class=\"dump-truncated\">... {$remaining} more</span>\n";
                break;
            }

            $formattedKey = is_int($key)
                ? "<span class=\"dump-number\">{$key}</span>"
                : "<span class=\"dump-string\">\"" . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . "\"</span>";

            $newPath = $path ? "{$path}.{$key}" : "[{$key}]";

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
            return '<span class="dump-circular">*RECURSION* ' . $shortClassName . '</span>';
        }

        self::$processedObjects[$objectId] = true;

        try {
            $reflection = new ReflectionObject($var);
            $properties = $reflection->getProperties();

            $output = "<span class=\"dump-class-name\">{$shortClassName}</span> {\n";
            $propCount = 0;
            $maxProps = min(self::$maxObjectProps, count($properties));

            foreach ($properties as $property) {
                if ($propCount >= $maxProps) {
                    $remaining = count($properties) - $maxProps;
                    $output .= "{$spaces}  <span class=\"dump-truncated\">... {$remaining} more</span>\n";
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

        return sprintf('(%.2f %s)', $bytes, $units[$index]);
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
}
