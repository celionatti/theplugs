<?php

declare(strict_types=1);

namespace Plugs\Debugger;

use Plugs\Http\Response\Response;

class PlugDebugger
{
    private static $instance = null;
    private $queries = [];
    private $errors = [];
    private $warnings = [];
    private $performance = [];
    private $memory = [];
    private $issues = [];
    private $logs = [];
    private $startTime;
    private $startMemory;
    private $enabled = true;

    private function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->initializeErrorHandling();
        $this->trackMemoryUsage();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Enable or disable debugging
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Initialize error and warning handling
     */
    private function initializeErrorHandling()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Track database queries
     */
    public function logQuery($sql, $params = [], $executionTime = 0, $connection = 'default')
    {
        if (!$this->enabled) return;

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $caller = $this->findQueryCaller($backtrace);

        $queryData = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'connection' => $connection,
            'timestamp' => microtime(true),
            'caller' => $caller,
            'memory_usage' => memory_get_usage(true),
            'formatted_sql' => $this->formatSql($sql, $params)
        ];

        $this->queries[] = $queryData;
        $this->analyzeQuery($queryData);
    }

    /**
     * Log custom messages
     */
    public function log($level, $message, $context = [])
    {
        if (!$this->enabled) return;

        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true)
        ];
    }

    /**
     * Find the actual caller of the query (skip framework internals)
     */
    private function findQueryCaller($backtrace)
    {
        $frameworkPaths = ['vendor/', 'framework/', 'database/', 'orm/', 'Plugs/Database', 'core/Database'];

        foreach ($backtrace as $trace) {
            if (isset($trace['file'])) {
                $isFrameworkFile = false;
                foreach ($frameworkPaths as $path) {
                    if (strpos($trace['file'], $path) !== false) {
                        $isFrameworkFile = true;
                        break;
                    }
                }

                if (!$isFrameworkFile) {
                    return [
                        'file' => $trace['file'],
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'] ?? 'unknown',
                        'class' => $trace['class'] ?? null
                    ];
                }
            }
        }

        return $backtrace[0] ?? ['file' => 'unknown', 'line' => 0];
    }

    /**
     * Format SQL with parameters
     */
    private function formatSql($sql, $params)
    {
        $formatted = $sql;
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $replacement = is_string($value) ? "'$value'" : (string)$value;
                if (is_numeric($key)) {
                    $formatted = preg_replace('/\?/', $replacement, $formatted, 1);
                } else {
                    $formatted = str_replace(":$key", $replacement, $formatted);
                }
            }
        }
        return $formatted;
    }

    /**
     * Analyze query for potential issues
     */
    private function analyzeQuery($queryData)
    {
        $sql = strtolower($queryData['sql']);
        $issues = [];

        // Check for N+1 queries
        if (count($this->queries) > 1) {
            $similarQueries = array_filter($this->queries, function ($q) use ($queryData) {
                return $this->isSimilarQuery($q['sql'], $queryData['sql']);
            });

            if (count($similarQueries) > 5) {
                $issues[] = [
                    'type' => 'N+1 Query Problem',
                    'severity' => 'high',
                    'message' => 'Potential N+1 query detected. ' . count($similarQueries) . ' similar queries found.',
                    'solution' => 'Consider using eager loading, joins, or caching to reduce query count.'
                ];
            }
        }

        // Check for slow queries
        if ($queryData['execution_time'] > 0.1) {
            $issues[] = [
                'type' => 'Slow Query',
                'severity' => $queryData['execution_time'] > 1 ? 'high' : 'medium',
                'message' => sprintf('Query took %.4f seconds to execute', $queryData['execution_time']),
                'solution' => 'Add indexes, optimize WHERE clauses, or consider query restructuring.'
            ];
        }

        // Check for SELECT *
        if (preg_match('/select\s+\*\s+from/i', $sql)) {
            $issues[] = [
                'type' => 'SELECT * Usage',
                'severity' => 'low',
                'message' => 'Using SELECT * can impact performance',
                'solution' => 'Specify only the columns you need in the SELECT clause.'
            ];
        }

        // Check for missing WHERE clause in UPDATE/DELETE
        if (preg_match('/^(update|delete)\s+/i', $sql) && !preg_match('/\s+where\s+/i', $sql)) {
            $issues[] = [
                'type' => 'Missing WHERE Clause',
                'severity' => 'critical',
                'message' => 'UPDATE/DELETE without WHERE clause detected',
                'solution' => 'Always use WHERE clause with UPDATE/DELETE to prevent accidental data loss.'
            ];
        }

        // Add issues to the query data
        if (!empty($issues)) {
            $queryData['issues'] = $issues;
            $this->issues = array_merge($this->issues, $issues);
        }
    }

    /**
     * Check if two queries are similar (for N+1 detection)
     */
    private function isSimilarQuery($sql1, $sql2)
    {
        $normalized1 = preg_replace('/\d+/', '?', strtolower(trim($sql1)));
        $normalized2 = preg_replace('/\d+/', '?', strtolower(trim($sql2)));
        $normalized1 = preg_replace('/\'[^\']*\'/', '?', $normalized1);
        $normalized2 = preg_replace('/\'[^\']*\'/', '?', $normalized2);

        return $normalized1 === $normalized2;
    }

    /**
     * Track memory usage at specific points
     */
    private function trackMemoryUsage()
    {
        $this->memory[] = [
            'timestamp' => microtime(true),
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true)
        ];
    }

    /**
     * Log performance markers
     */
    public function markPerformance($label, $data = [])
    {
        if (!$this->enabled) return;

        $this->performance[] = [
            'label' => $label,
            'timestamp' => microtime(true),
            'memory' => memory_get_usage(true),
            'data' => $data
        ];
        $this->trackMemoryUsage();
    }

    /**
     * Handle PHP errors
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!$this->enabled) return false;

        $errorData = [
            'type' => 'PHP Error',
            'level' => $this->getErrorLevelName($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'timestamp' => microtime(true)
        ];

        if ($errno === E_WARNING || $errno === E_USER_WARNING) {
            $this->warnings[] = $errorData;
        } else {
            $this->errors[] = $errorData;
        }

        return false;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception)
    {
        if (!$this->enabled) return;

        $this->errors[] = [
            'type' => 'Uncaught Exception',
            'level' => 'Fatal',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Handle script shutdown
     */
    public function handleShutdown()
    {
        if (!$this->enabled) return;

        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->errors[] = [
                'type' => 'Fatal Error',
                'level' => 'Fatal',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => microtime(true)
            ];
        }
    }

    /**
     * Get error level name
     */
    private function getErrorLevelName($errno)
    {
        $levels = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $levels[$errno] ?? 'Unknown Error';
    }

    /**
     * Get comprehensive debug report
     */
    public function getDebugReport()
    {
        if (!$this->enabled) {
            return ['message' => 'Debugging is disabled'];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $report = [
            'execution_time' => $endTime - $this->startTime,
            'memory_usage' => [
                'start' => $this->startMemory,
                'end' => $endMemory,
                'peak' => memory_get_peak_usage(true),
                'difference' => $endMemory - $this->startMemory
            ],
            'queries' => [
                'total' => count($this->queries),
                'details' => $this->queries,
                'total_execution_time' => array_sum(array_column($this->queries, 'execution_time'))
            ],
            'errors' => [
                'total' => count($this->errors),
                'details' => $this->errors
            ],
            'warnings' => [
                'total' => count($this->warnings),
                'details' => $this->warnings
            ],
            'logs' => [
                'total' => count($this->logs),
                'details' => $this->logs
            ],
            'issues' => [
                'total' => count($this->issues),
                'details' => $this->issues,
                'by_severity' => $this->groupIssuesBySeverity()
            ],
            'performance_markers' => $this->performance,
            'request' => $this->getRequestInfo(),
            'files' => get_included_files(),
            'recommendations' => $this->generateRecommendations()
        ];

        return $report;
    }

    /**
     * Get request information
     */
    private function getRequestInfo()
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
    }

    /**
     * Group issues by severity
     */
    private function groupIssuesBySeverity()
    {
        $grouped = ['critical' => [], 'high' => [], 'medium' => [], 'low' => []];

        foreach ($this->issues as $issue) {
            $severity = $issue['severity'] ?? 'low';
            $grouped[$severity][] = $issue;
        }

        return $grouped;
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations()
    {
        $recommendations = [];

        if (count($this->queries) > 50) {
            $recommendations[] = [
                'type' => 'Database',
                'message' => 'High query count detected (' . count($this->queries) . ' queries)',
                'solution' => 'Consider implementing caching, eager loading, or query optimization'
            ];
        }

        $memoryUsed = memory_get_peak_usage(true) - $this->startMemory;
        if ($memoryUsed > 50 * 1024 * 1024) {
            $recommendations[] = [
                'type' => 'Memory',
                'message' => 'High memory usage: ' . $this->formatBytes($memoryUsed),
                'solution' => 'Check for memory leaks, optimize data structures, or implement pagination'
            ];
        }

        $executionTime = microtime(true) - $this->startTime;
        if ($executionTime > 5) {
            $recommendations[] = [
                'type' => 'Performance',
                'message' => sprintf('Slow execution time: %.2f seconds', $executionTime),
                'solution' => 'Profile bottlenecks, optimize queries, implement caching, or use async processing'
            ];
        }

        return $recommendations;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Render the debug bar HTML - safe to inject after output buffering
     */
    public function render()
    {
        if (!$this->enabled) {
            return '';
        }

        try {
            $data = $this->getDebugReport();
            return $this->generateDebugBarHtml($data);
        } catch (\Exception $e) {
            // Fallback to simple debug bar if JSON encoding fails
            $data = $this->getDebugReport();
            return $this->generateSimpleDebugBar($data);
        }
    }

    /**
     * Generate modern debug bar HTML
     */
    private function generateDebugBarHtml($data)
    {
        // Properly escape JSON for inline script with more robust handling
        $jsonData = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);

        $html = <<<HTML
            <div id="plugs-debug-bar" style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 999999; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;">
                <div id="plugs-debug-tabs" style="background: #1e1e1e; color: #fff; display: flex; align-items: center; padding: 0; border-top: 3px solid #007acc; box-shadow: 0 -2px 10px rgba(0,0,0,0.3);">
                    <div style="padding: 10px 15px; background: #007acc; font-weight: 600; cursor: pointer; user-select: none;" onclick="plugsDebugToggle()">
                        ⚡ Debug Bar
                    </div>
                    <div class="plugs-tab" data-panel="overview" style="padding: 10px 18px; cursor: pointer; border-left: 1px solid #333; transition: background 0.2s;" onclick="plugsDebugShowPanel('overview')">
                        Overview
                    </div>
                    <div class="plugs-tab" data-panel="queries" style="padding: 10px 18px; cursor: pointer; border-left: 1px solid #333; transition: background 0.2s;" onclick="plugsDebugShowPanel('queries')">
                        Queries <span class="plugs-badge" id="plugs-queries-count">0</span>
                    </div>
                    <div class="plugs-tab" data-panel="timeline" style="padding: 10px 18px; cursor: pointer; border-left: 1px solid #333; transition: background 0.2s;" onclick="plugsDebugShowPanel('timeline')">
                        Timeline
                    </div>
                    <div class="plugs-tab" data-panel="request" style="padding: 10px 18px; cursor: pointer; border-left: 1px solid #333; transition: background 0.2s;" onclick="plugsDebugShowPanel('request')">
                        Request
                    </div>
                    <div class="plugs-tab" data-panel="issues" style="padding: 10px 18px; cursor: pointer; border-left: 1px solid #333; transition: background 0.2s;" onclick="plugsDebugShowPanel('issues')">
                        Issues <span class="plugs-badge plugs-badge-warning" id="plugs-issues-count">0</span>
                    </div>
                    <div class="plugs-tab" data-panel="logs" style="padding: 10px 18px; cursor: pointer; border-left: 1px solid #333; transition: background 0.2s;" onclick="plugsDebugShowPanel('logs')">
                        Logs <span class="plugs-badge" id="plugs-logs-count">0</span>
                    </div>
                    <div style="margin-left: auto; padding: 10px 18px; font-size: 12px; color: #888;">
                        <span id="plugs-debug-time">0ms</span> | <span id="plugs-debug-memory">0MB</span>
                    </div>
                </div>
                
                <div id="plugs-debug-content" style="display: none; background: #2d2d2d; color: #d4d4d4; max-height: 500px; overflow-y: auto;">
                    <div id="panel-overview" class="plugs-panel" style="padding: 20px; display: none;"></div>
                    <div id="panel-queries" class="plugs-panel" style="padding: 20px; display: none;"></div>
                    <div id="panel-timeline" class="plugs-panel" style="padding: 20px; display: none;"></div>
                    <div id="panel-request" class="plugs-panel" style="padding: 20px; display: none;"></div>
                    <div id="panel-issues" class="plugs-panel" style="padding: 20px; display: none;"></div>
                    <div id="panel-logs" class="plugs-panel" style="padding: 20px; display: none;"></div>
                </div>
            </div>

            <style>
            .plugs-tab:hover { background: #333 !important; }
            .plugs-tab.active { background: #007acc !important; }
            .plugs-badge {
                background: #d16969;
                padding: 2px 7px;
                border-radius: 10px;
                font-size: 11px;
                margin-left: 6px;
                font-weight: 600;
            }
            .plugs-badge-warning { background: #d7ba7d; color: #1e1e1e; }
            .plugs-stat-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            .plugs-stat-card {
                background: #1e1e1e;
                padding: 18px;
                border-radius: 6px;
                border-left: 4px solid #007acc;
            }
            .plugs-stat-label {
                color: #888;
                font-size: 12px;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .plugs-stat-value {
                font-size: 28px;
                font-weight: 600;
                color: #4ec9b0;
            }
            .plugs-query-item {
                background: #1e1e1e;
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 6px;
                border-left: 4px solid #28a745;
            }
            .plugs-query-item.slow { border-left-color: #ffc107; }
            .plugs-query-item.very-slow { border-left-color: #dc3545; }
            .plugs-query-sql {
                font-family: 'Consolas', 'Monaco', monospace;
                color: #ce9178;
                margin-bottom: 10px;
                font-size: 13px;
                line-height: 1.6;
            }
            .plugs-query-meta {
                font-size: 12px;
                color: #888;
                display: flex;
                gap: 15px;
            }
            .plugs-timeline-item {
                background: #1e1e1e;
                padding: 12px 15px;
                margin-bottom: 8px;
                border-radius: 6px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .plugs-issue-item {
                background: #1e1e1e;
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 6px;
            }
            .plugs-issue-critical { border-left: 4px solid #dc3545; }
            .plugs-issue-high { border-left: 4px solid #fd7e14; }
            .plugs-issue-medium { border-left: 4px solid #ffc107; }
            .plugs-issue-low { border-left: 4px solid #17a2b8; }
            .plugs-log-item {
                padding: 10px 15px;
                margin-bottom: 6px;
                border-radius: 4px;
                font-family: 'Consolas', 'Monaco', monospace;
                font-size: 13px;
            }
            .plugs-log-error { background: #5a1d1d; border-left: 3px solid #d16969; }
            .plugs-log-warning { background: #5a4e1d; border-left: 3px solid #d7ba7d; }
            .plugs-log-info { background: #1d3a5a; border-left: 3px solid #569cd6; }
            .plugs-log-debug { background: #1e1e1e; border-left: 3px solid #888; }
            </style>

            <script>
            (function() {
                var plugsDebugData = $jsonData;
                
                var plugsDebugOpen = false;
                var plugsCurrentPanel = 'overview';

                window.plugsDebugToggle = function() {
                    plugsDebugOpen = !plugsDebugOpen;
                    var content = document.getElementById('plugs-debug-content');
                    if (content) {
                        content.style.display = plugsDebugOpen ? 'block' : 'none';
                    }
                    if (plugsDebugOpen && plugsCurrentPanel) {
                        window.plugsDebugShowPanel(plugsCurrentPanel);
                    }
                };

                window.plugsDebugShowPanel = function(panelName) {
                    plugsCurrentPanel = panelName;
                    var panels = document.getElementsByClassName('plugs-panel');
                    for (var i = 0; i < panels.length; i++) {
                        panels[i].style.display = 'none';
                    }
                    
                    var tabs = document.getElementsByClassName('plugs-tab');
                    for (var i = 0; i < tabs.length; i++) {
                        tabs[i].classList.remove('active');
                    }
                    
                    var panel = document.getElementById('panel-' + panelName);
                    if (panel) {
                        panel.style.display = 'block';
                    }
                    
                    var activeTab = document.querySelector('.plugs-tab[data-panel="' + panelName + '"]');
                    if (activeTab) {
                        activeTab.classList.add('active');
                    }
                    
                    plugsRenderPanel(panelName);
                };

                function plugsRenderPanel(panelName) {
                    var panel = document.getElementById('panel-' + panelName);
                    if (!panel) return;
                    
                    var html = '';
                    
                    switch(panelName) {
                        case 'overview':
                            html = plugsRenderOverview();
                            break;
                        case 'queries':
                            html = plugsRenderQueries();
                            break;
                        case 'timeline':
                            html = plugsRenderTimeline();
                            break;
                        case 'request':
                            html = plugsRenderRequest();
                            break;
                        case 'issues':
                            html = plugsRenderIssues();
                            break;
                        case 'logs':
                            html = plugsRenderLogs();
                            break;
                    }
                    
                    panel.innerHTML = html;
                }

                function plugsRenderOverview() {
                    if (!plugsDebugData) return '<div>No debug data available</div>';
                    
                    var html = '<h3 style="margin-top: 0; color: #fff; font-size: 18px;">Performance Overview</h3>';
                    html += '<div class="plugs-stat-grid">';
                    html += '<div class="plugs-stat-card"><div class="plugs-stat-label">Execution Time</div>';
                    html += '<div class="plugs-stat-value">' + ((plugsDebugData.execution_time || 0) * 1000).toFixed(2) + 'ms</div></div>';
                    html += '<div class="plugs-stat-card"><div class="plugs-stat-label">Peak Memory</div>';
                    html += '<div class="plugs-stat-value">' + plugsFormatBytes(plugsDebugData.memory_usage?.peak || 0) + '</div></div>';
                    html += '<div class="plugs-stat-card"><div class="plugs-stat-label">Database Queries</div>';
                    html += '<div class="plugs-stat-value">' + (plugsDebugData.queries?.total || 0) + '</div></div>';
                    html += '<div class="plugs-stat-card"><div class="plugs-stat-label">Files Loaded</div>';
                    html += '<div class="plugs-stat-value">' + (plugsDebugData.files?.length || 0) + '</div></div>';
                    html += '</div>';
                    
                    if (plugsDebugData.recommendations && plugsDebugData.recommendations.length > 0) {
                        html += '<h4 style="color: #fff; margin-top: 25px;">Recommendations</h4>';
                        plugsDebugData.recommendations.forEach(function(rec) {
                            html += '<div class="plugs-issue-item plugs-issue-medium">';
                            html += '<strong>' + (rec.type || 'General') + ':</strong> ' + (rec.message || '') + '<br>';
                            html += '<em style="color: #888;">💡 ' + (rec.solution || '') + '</em></div>';
                        });
                    }
                    
                    return html;
                }

                function plugsRenderQueries() {
                    if (!plugsDebugData || !plugsDebugData.queries) {
                        return '<p style="color: #888;">No query data available</p>';
                    }
                    
                    var html = '<h3 style="margin-top: 0; color: #fff; font-size: 18px;">Database Queries (' + (plugsDebugData.queries.total || 0) + ')</h3>';
                    
                    if (plugsDebugData.queries.total > 0) {
                        html += '<div style="margin-bottom: 15px; color: #888;">Total execution time: ' + 
                                ((plugsDebugData.queries.total_execution_time || 0) * 1000).toFixed(2) + 'ms</div>';
                        
                        (plugsDebugData.queries.details || []).forEach(function(query) {
                            var slowClass = '';
                            if (query.execution_time > 1) slowClass = 'very-slow';
                            else if (query.execution_time > 0.1) slowClass = 'slow';
                            
                            html += '<div class="plugs-query-item ' + slowClass + '">';
                            html += '<div class="plugs-query-sql">' + plugsEscapeHtml(query.formatted_sql || query.sql || '') + '</div>';
                            html += '<div class="plugs-query-meta">';
                            html += '<span>⏱ ' + ((query.execution_time || 0) * 1000).toFixed(2) + 'ms</span>';
                            if (query.caller) {
                                html += '<span>📍 ' + plugsBasename(query.caller.file || '') + ':' + (query.caller.line || 0) + '</span>';
                            }
                            if (query.params && query.params.length > 0) {
                                html += '<span>🔗 ' + query.params.length + ' bindings</span>';
                            }
                            html += '</div></div>';
                        });
                    } else {
                        html += '<p style="color: #888;">No queries executed</p>';
                    }
                    
                    return html;
                }

                function plugsRenderTimeline() {
                    if (!plugsDebugData) return '<p style="color: #888;">No timeline data available</p>';
                    
                    var html = '<h3 style="margin-top: 0; color: #fff; font-size: 18px;">Execution Timeline</h3>';
                    
                    if (plugsDebugData.performance_markers && plugsDebugData.performance_markers.length > 0) {
                        var startTime = plugsDebugData.performance_markers[0].timestamp;
                        plugsDebugData.performance_markers.forEach(function(marker) {
                            var relTime = ((marker.timestamp - startTime) * 1000).toFixed(2);
                            html += '<div class="plugs-timeline-item">';
                            html += '<div>' + plugsEscapeHtml(marker.label || '') + '</div>';
                            html += '<div style="color: #4ec9b0;">' + relTime + 'ms</div>';
                            html += '</div>';
                        });
                    } else {
                        html += '<p style="color: #888;">No timeline markers recorded</p>';
                    }
                    
                    return html;
                }

                function plugsRenderRequest() {
                    if (!plugsDebugData || !plugsDebugData.request) {
                        return '<p style="color: #888;">No request data available</p>';
                    }
                    
                    var html = '<h3 style="margin-top: 0; color: #fff; font-size: 18px;">Request Information</h3>';
                    html += '<div style="display: grid; gap: 15px;">';
                    html += '<div><strong style="color: #569cd6;">Method:</strong> ' + (plugsDebugData.request.method || '') + '</div>';
                    html += '<div><strong style="color: #569cd6;">URI:</strong> ' + plugsEscapeHtml(plugsDebugData.request.uri || '') + '</div>';
                    html += '<div><strong style="color: #569cd6;">IP Address:</strong> ' + (plugsDebugData.request.ip || '') + '</div>';
                    html += '<div><strong style="color: #569cd6;">User Agent:</strong> ' + plugsEscapeHtml(plugsDebugData.request.user_agent || '') + '</div>';
                    html += '</div>';
                    return html;
                }

                function plugsRenderIssues() {
                    if (!plugsDebugData || !plugsDebugData.issues) {
                        return '<p style="color: #888;">No issues data available</p>';
                    }
                    
                    var html = '<h3 style="margin-top: 0; color: #fff; font-size: 18px;">Issues & Warnings</h3>';
                    
                    if (plugsDebugData.issues.total > 0) {
                        var severities = ['critical', 'high', 'medium', 'low'];
                        severities.forEach(function(severity) {
                            var issues = plugsDebugData.issues.by_severity?.[severity] || [];
                            if (issues.length > 0) {
                                issues.forEach(function(issue) {
                                    html += '<div class="plugs-issue-item plugs-issue-' + severity + '">';
                                    html += '<strong style="text-transform: uppercase;">' + severity + ': ' + (issue.type || 'Issue') + '</strong><br>';
                                    html += '<div style="margin: 8px 0;">' + (issue.message || '') + '</div>';
                                    html += '<em style="color: #888;">💡 Solution: ' + (issue.solution || '') + '</em>';
                                    html += '</div>';
                                });
                            }
                        });
                    } else {
                        html += '<p style="color: #888;">No issues detected</p>';
                    }
                    
                    return html;
                }

                function plugsRenderLogs() {
                    if (!plugsDebugData || !plugsDebugData.logs) {
                        return '<p style="color: #888;">No logs data available</p>';
                    }
                    
                    var html = '<h3 style="margin-top: 0; color: #fff; font-size: 18px;">Application Logs (' + (plugsDebugData.logs.total || 0) + ')</h3>';
                    
                    if (plugsDebugData.logs.total > 0) {
                        (plugsDebugData.logs.details || []).forEach(function(log) {
                            html += '<div class="plugs-log-item plugs-log-' + (log.level || 'info') + '">';
                            html += '[' + (log.level?.toUpperCase() || 'INFO') + '] ' + plugsEscapeHtml(log.message || '');
                            html += '</div>';
                        });
                    } else {
                        html += '<p style="color: #888;">No logs recorded</p>';
                    }
                    
                    return html;
                }

                function plugsEscapeHtml(text) {
                    if (text === null || text === undefined) return '';
                    var div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                }

                function plugsFormatBytes(bytes) {
                    if (!bytes) return '0 B';
                    var units = ['B', 'KB', 'MB', 'GB'];
                    var i = 0;
                    while (bytes >= 1024 && i < units.length - 1) {
                        bytes /= 1024;
                        i++;
                    }
                    return bytes.toFixed(2) + ' ' + units[i];
                }

                function plugsBasename(path) {
                    if (!path) return 'unknown';
                    return path.split('/').pop().split('\\\\').pop();
                }

                function plugsInitDebugBar() {
                    if (!plugsDebugData) {
                        console.error('No debug data available');
                        return;
                    }
                    
                    var timeEl = document.getElementById('plugs-debug-time');
                    var memoryEl = document.getElementById('plugs-debug-memory');
                    var queriesEl = document.getElementById('plugs-queries-count');
                    var issuesEl = document.getElementById('plugs-issues-count');
                    var logsEl = document.getElementById('plugs-logs-count');
                    
                    if (timeEl) timeEl.textContent = ((plugsDebugData.execution_time || 0) * 1000).toFixed(2) + 'ms';
                    if (memoryEl) memoryEl.textContent = plugsFormatBytes(plugsDebugData.memory_usage?.peak || 0);
                    if (queriesEl) queriesEl.textContent = plugsDebugData.queries?.total || 0;
                    if (issuesEl) issuesEl.textContent = plugsDebugData.issues?.total || 0;
                    if (logsEl) logsEl.textContent = plugsDebugData.logs?.total || 0;
                }

                // Initialize when DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', plugsInitDebugBar);
                } else {
                    plugsInitDebugBar();
                }
            })();
            </script>
            HTML;

        // Replace the JSON placeholder with actual data
        $html = str_replace('$jsonData', $jsonData, $html);

        return $html;
    }

    /**
     * Simple debug bar as fallback
     */
    private function generateSimpleDebugBar($data)
    {
        $time = ($data['execution_time'] * 1000) . 'ms';
        $memory = $this->formatBytes($data['memory_usage']['peak']);
        $queries = $data['queries']['total'];
        $errors = $data['errors']['total'];

        return <<<HTML
        <div id="plugs-debug-bar" style="position: fixed; bottom: 0; left: 0; right: 0; background: #1e1e1e; color: #fff; padding: 10px; font-family: monospace; font-size: 12px; z-index: 999999;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>⚡ Debug Bar</strong> | 
                    Time: {$time} | 
                    Memory: {$memory} | 
                    Queries: {$queries} | 
                    Errors: {$errors}
                </div>
                <button onclick="this.parentElement.parentElement.style.display='none'" style="background: #dc3545; color: white; border: none; padding: 2px 8px; cursor: pointer;">×</button>
            </div>
        </div>
        HTML;
    }


    /**
     * Inject debug bar into response - works with output buffering
     */
    public function injectIntoResponse(&$content)
    {
        if (!$this->enabled) {
            return;
        }

        // Only inject into HTML responses
        if (!is_string($content) || stripos($content, '</body>') === false) {
            return;
        }

        $debugBar = $this->render();
        $content = preg_replace('/<\/body>/i', $debugBar . "\n</body>", $content, 1);
    }

    /**
     * Display debug panel (old method for backwards compatibility)
     */
    public function displayDebugPanel()
    {
        if (!$this->enabled) {
            return;
        }

        echo $this->render();
    }

    /**
     * Export debug report as JSON
     */
    public function exportReport($filename = null)
    {
        if (!$this->enabled) {
            return false;
        }

        $filename = $filename ?: 'debug_report_' . date('Y-m-d_H-i-s') . '.json';
        $report = $this->getDebugReport();

        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

        return $filename;
    }

    /**
     * Clear all debug data
     */
    public function clear()
    {
        $this->queries = [];
        $this->errors = [];
        $this->warnings = [];
        $this->performance = [];
        $this->memory = [];
        $this->issues = [];
        $this->logs = [];
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Get query count
     */
    public function getQueryCount()
    {
        return count($this->queries);
    }

    /**
     * Get error count
     */
    public function getErrorCount()
    {
        return count($this->errors);
    }

    /**
     * Get issue count
     */
    public function getIssueCount()
    {
        return count($this->issues);
    }
}
