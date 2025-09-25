<?php

declare(strict_types=1);

namespace Plugs\Debugger;

class PlugDebugger
{
    private static $instance = null;
    private $queries = [];
    private $errors = [];
    private $warnings = [];
    private $performance = [];
    private $memory = [];
    private $issues = [];
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
     * Find the actual caller of the query (skip framework internals)
     */
    private function findQueryCaller($backtrace)
    {
        $frameworkPaths = ['vendor/', 'framework/', 'database/', 'orm/'];

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
                $replacement = is_string($value) ? "'$value'" : $value;
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
        if (!$this->enabled) return;

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

        // Don't prevent default error handling
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
            'issues' => [
                'total' => count($this->issues),
                'details' => $this->issues,
                'by_severity' => $this->groupIssuesBySeverity()
            ],
            'performance_markers' => $this->performance,
            'recommendations' => $this->generateRecommendations()
        ];

        return $report;
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

        // Query recommendations
        if (count($this->queries) > 50) {
            $recommendations[] = [
                'type' => 'Database',
                'message' => 'High query count detected (' . count($this->queries) . ' queries)',
                'solution' => 'Consider implementing caching, eager loading, or query optimization'
            ];
        }

        // Memory recommendations
        $memoryUsed = memory_get_peak_usage(true) - $this->startMemory;
        if ($memoryUsed > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = [
                'type' => 'Memory',
                'message' => 'High memory usage: ' . $this->formatBytes($memoryUsed),
                'solution' => 'Check for memory leaks, optimize data structures, or implement pagination'
            ];
        }

        // Execution time recommendations
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
     * Output debug information as HTML
     */
    public function displayDebugPanel()
    {
        if (!$this->enabled) {
            return;
        }

        $report = $this->getDebugReport();

        echo $this->generateDebugHtml($report);
    }

    /**
     * Generate HTML for debug panel
     */
    private function generateDebugHtml($report)
    {
        $html = '
        <style>
            #debug-panel {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #1a1a1a;
                color: #fff;
                font-family: monospace;
                font-size: 12px;
                max-height: 50vh;
                overflow-y: auto;
                z-index: 10000;
                border-top: 3px solid #007cba;
            }
            #debug-panel .debug-header {
                background: #333;
                padding: 10px;
                border-bottom: 1px solid #555;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            #debug-panel .debug-content {
                padding: 15px;
            }
            #debug-panel .debug-section {
                margin-bottom: 20px;
            }
            #debug-panel .debug-section h3 {
                color: #007cba;
                margin: 0 0 10px 0;
                font-size: 14px;
            }
            #debug-panel .stat-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            #debug-panel .stat-card {
                background: #2a2a2a;
                padding: 10px;
                border-radius: 4px;
                border-left: 3px solid #007cba;
            }
            #debug-panel .stat-label {
                color: #999;
                font-size: 11px;
            }
            #debug-panel .stat-value {
                color: #fff;
                font-size: 16px;
                font-weight: bold;
            }
            #debug-panel .query-item {
                background: #2a2a2a;
                margin: 5px 0;
                padding: 10px;
                border-radius: 4px;
                border-left: 3px solid #28a745;
            }
            #debug-panel .query-item.slow {
                border-left-color: #ffc107;
            }
            #debug-panel .query-item.very-slow {
                border-left-color: #dc3545;
            }
            #debug-panel .issue-item {
                background: #2a2a2a;
                margin: 5px 0;
                padding: 10px;
                border-radius: 4px;
            }
            #debug-panel .issue-critical {
                border-left: 3px solid #dc3545;
            }
            #debug-panel .issue-high {
                border-left: 3px solid #fd7e14;
            }
            #debug-panel .issue-medium {
                border-left: 3px solid #ffc107;
            }
            #debug-panel .issue-low {
                border-left: 3px solid #28a745;
            }
            #debug-panel .toggle-btn {
                background: #007cba;
                color: white;
                border: none;
                padding: 5px 10px;
                cursor: pointer;
                border-radius: 3px;
            }
            #debug-panel .close-btn {
                background: #dc3545;
                color: white;
                border: none;
                padding: 5px 10px;
                cursor: pointer;
                border-radius: 3px;
                margin-left: 10px;
            }
            .debug-hidden {
                display: none;
            }
        </style>
        
        <div id="debug-panel">
            <div class="debug-header">
                <strong>🐛 Framework Debugger</strong>
                <div>
                    <button class="toggle-btn" onclick="toggleDebugContent()">Toggle Details</button>
                    <button class="close-btn" onclick="closeDebugPanel()">Close</button>
                </div>
            </div>
            <div class="debug-content" id="debug-content">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-label">Execution Time</div>
                        <div class="stat-value">' . number_format($report['execution_time'], 4) . 's</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Memory Usage</div>
                        <div class="stat-value">' . $this->formatBytes($report['memory_usage']['peak']) . '</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Database Queries</div>
                        <div class="stat-value">' . $report['queries']['total'] . '</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Issues Found</div>
                        <div class="stat-value">' . $report['issues']['total'] . '</div>
                    </div>
                </div>';

        // Issues section
        if (!empty($report['issues']['details'])) {
            $html .= '<div class="debug-section">
                <h3>🚨 Issues & Recommendations</h3>';

            foreach ($report['issues']['details'] as $issue) {
                $severityClass = 'issue-' . $issue['severity'];
                $html .= '<div class="issue-item ' . $severityClass . '">
                    <strong>' . strtoupper($issue['severity']) . ': ' . $issue['type'] . '</strong><br>
                    ' . $issue['message'] . '<br>
                    <em>Solution: ' . $issue['solution'] . '</em>
                </div>';
            }

            $html .= '</div>';
        }

        // Top queries section
        if (!empty($report['queries']['details'])) {
            $html .= '<div class="debug-section">
                <h3>🔍 Recent Queries</h3>';

            $recentQueries = array_slice($report['queries']['details'], -5);
            foreach ($recentQueries as $query) {
                $slowClass = '';
                if ($query['execution_time'] > 1) {
                    $slowClass = 'very-slow';
                } elseif ($query['execution_time'] > 0.1) {
                    $slowClass = 'slow';
                }

                $html .= '<div class="query-item ' . $slowClass . '">
                    <strong>' . number_format($query['execution_time'], 4) . 's</strong> - 
                    ' . htmlspecialchars(substr($query['formatted_sql'], 0, 100)) . '...<br>
                    <small>File: ' . basename($query['caller']['file']) . ':' . $query['caller']['line'] . '</small>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '
            </div>
        </div>
        
        <script>
            function toggleDebugContent() {
                const content = document.getElementById("debug-content");
                content.classList.toggle("debug-hidden");
            }
            
            function closeDebugPanel() {
                const panel = document.getElementById("debug-panel");
                panel.style.display = "none";
            }
        </script>';

        return $html;
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
