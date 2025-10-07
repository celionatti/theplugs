<?php

declare(strict_types=1);

namespace Plugs\Debugger;

use Plugs\Debugger\Collectors\LogCollector;
use Plugs\Debugger\Collectors\FileCollector;
use Plugs\Debugger\Collectors\QueryCollector;
use Plugs\Debugger\Collectors\ConfigCollector;
use Plugs\Debugger\Collectors\MemoryCollector;
use Plugs\Debugger\Collectors\RequestCollector;
use Plugs\Debugger\Collectors\SessionCollector;
use Plugs\Debugger\Collectors\TimelineCollector;
use Plugs\Debugger\Collectors\CollectorInterface;

class DebugBar
{
    private static $instance = null;
    private $collectors = [];
    private $enabled = true;
    private $startTime;
    private $startMemory;

    private function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
        $this->initializeCollectors();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeCollectors()
    {
        $this->collectors = [
            'queries' => new QueryCollector(),
            'timeline' => new TimelineCollector(),
            'request' => new RequestCollector(),
            'memory' => new MemoryCollector(),
            'files' => new FileCollector(),
            'logs' => new LogCollector(),
            'session' => new SessionCollector(),
            'config' => new ConfigCollector(),
        ];
    }

    public function enable()
    {
        $this->enabled = true;
    }

    public function disable()
    {
        $this->enabled = false;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function addCollector($name, CollectorInterface $collector)
    {
        $this->collectors[$name] = $collector;
    }

    public function getCollector($name)
    {
        return $this->collectors[$name] ?? null;
    }

    public function getData()
    {
        $data = [];
        foreach ($this->collectors as $name => $collector) {
            $data[$name] = $collector->collect();
        }

        $data['performance'] = [
            'time' => round((microtime(true) - $this->startTime) * 1000, 2),
            'memory' => $this->formatBytes(memory_get_usage() - $this->startMemory),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage()),
        ];

        return $data;
    }

    public function render()
    {
        if (!$this->enabled) {
            return '';
        }

        $data = $this->getData();
        $jsonData = json_encode($data);

        return $this->getHtml($jsonData);
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getHtml($jsonData)
    {
        return <<<HTML
            <div id="debug-bar" style="position: fixed; bottom: 0; left: 0; right: 0; z-index: 999999; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                <div id="debug-bar-tabs" style="background: #1e1e1e; color: #fff; display: flex; align-items: center; padding: 0; border-top: 3px solid #007acc; box-shadow: 0 -2px 10px rgba(0,0,0,0.3);">
                    <div style="padding: 8px 12px; background: #007acc; font-weight: bold; cursor: pointer;" onclick="debugBarToggle()">
                        ⚡ Debug
                    </div>
                    <div class="debug-tab" data-panel="overview" style="padding: 8px 16px; cursor: pointer; border-left: 1px solid #333;" onclick="debugBarShowPanel('overview')">
                        Overview
                    </div>
                    <div class="debug-tab" data-panel="queries" style="padding: 8px 16px; cursor: pointer; border-left: 1px solid #333;" onclick="debugBarShowPanel('queries')">
                        Queries <span class="debug-badge" id="queries-count">0</span>
                    </div>
                    <div class="debug-tab" data-panel="timeline" style="padding: 8px 16px; cursor: pointer; border-left: 1px solid #333;" onclick="debugBarShowPanel('timeline')">
                        Timeline
                    </div>
                    <div class="debug-tab" data-panel="request" style="padding: 8px 16px; cursor: pointer; border-left: 1px solid #333;" onclick="debugBarShowPanel('request')">
                        Request
                    </div>
                    <div class="debug-tab" data-panel="logs" style="padding: 8px 16px; cursor: pointer; border-left: 1px solid #333;" onclick="debugBarShowPanel('logs')">
                        Logs <span class="debug-badge" id="logs-count">0</span>
                    </div>
                    <div style="margin-left: auto; padding: 8px 16px; font-size: 12px; color: #888;">
                        <span id="debug-time">0ms</span> | <span id="debug-memory">0MB</span>
                    </div>
                </div>
                
                <div id="debug-bar-content" style="display: none; background: #2d2d2d; color: #d4d4d4; max-height: 400px; overflow-y: auto;">
                    <div id="panel-overview" class="debug-panel" style="padding: 20px; display: none;">
                        <h3 style="margin-top: 0; color: #fff;">Performance Overview</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div style="background: #1e1e1e; padding: 15px; border-radius: 4px;">
                                <div style="color: #888; font-size: 12px; margin-bottom: 5px;">Execution Time</div>
                                <div style="font-size: 24px; color: #4ec9b0;" id="overview-time">0ms</div>
                            </div>
                            <div style="background: #1e1e1e; padding: 15px; border-radius: 4px;">
                                <div style="color: #888; font-size: 12px; margin-bottom: 5px;">Memory Usage</div>
                                <div style="font-size: 24px; color: #ce9178;" id="overview-memory">0MB</div>
                            </div>
                            <div style="background: #1e1e1e; padding: 15px; border-radius: 4px;">
                                <div style="color: #888; font-size: 12px; margin-bottom: 5px;">Database Queries</div>
                                <div style="font-size: 24px; color: #dcdcaa;" id="overview-queries">0</div>
                            </div>
                            <div style="background: #1e1e1e; padding: 15px; border-radius: 4px;">
                                <div style="color: #888; font-size: 12px; margin-bottom: 5px;">Files Loaded</div>
                                <div style="font-size: 24px; color: #569cd6;" id="overview-files">0</div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="panel-queries" class="debug-panel" style="padding: 20px; display: none;">
                        <h3 style="margin-top: 0; color: #fff;">Database Queries</h3>
                        <div id="queries-list"></div>
                    </div>
                    
                    <div id="panel-timeline" class="debug-panel" style="padding: 20px; display: none;">
                        <h3 style="margin-top: 0; color: #fff;">Execution Timeline</h3>
                        <div id="timeline-list"></div>
                    </div>
                    
                    <div id="panel-request" class="debug-panel" style="padding: 20px; display: none;">
                        <h3 style="margin-top: 0; color: #fff;">Request Information</h3>
                        <div id="request-info"></div>
                    </div>
                    
                    <div id="panel-logs" class="debug-panel" style="padding: 20px; display: none;">
                        <h3 style="margin-top: 0; color: #fff;">Application Logs</h3>
                        <div id="logs-list"></div>
                    </div>
                </div>
            </div>

            <style>
            .debug-tab:hover {
                background: #333 !important;
            }
            .debug-tab.active {
                background: #007acc !important;
            }
            .debug-badge {
                background: #d16969;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 11px;
                margin-left: 5px;
            }
            .debug-query {
                background: #1e1e1e;
                padding: 12px;
                margin-bottom: 10px;
                border-radius: 4px;
                border-left: 3px solid #007acc;
            }
            .debug-query-sql {
                font-family: 'Courier New', monospace;
                color: #ce9178;
                margin-bottom: 8px;
            }
            .debug-query-meta {
                font-size: 12px;
                color: #888;
            }
            .debug-timeline-item {
                background: #1e1e1e;
                padding: 10px;
                margin-bottom: 8px;
                border-radius: 4px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .debug-log-item {
                padding: 8px 12px;
                margin-bottom: 5px;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
            }
            .debug-log-error { background: #5a1d1d; border-left: 3px solid #d16969; }
            .debug-log-warning { background: #5a4e1d; border-left: 3px solid #d7ba7d; }
            .debug-log-info { background: #1d3a5a; border-left: 3px solid #569cd6; }
            .debug-log-debug { background: #1e1e1e; border-left: 3px solid #888; }
            </style>

            <script>
            var debugBarData = $jsonData;
            var debugBarOpen = false;
            var currentPanel = 'overview';

            function debugBarToggle() {
                debugBarOpen = !debugBarOpen;
                document.getElementById('debug-bar-content').style.display = debugBarOpen ? 'block' : 'none';
                if (debugBarOpen && currentPanel) {
                    debugBarShowPanel(currentPanel);
                }
            }

            function debugBarShowPanel(panelName) {
                currentPanel = panelName;
                var panels = document.getElementsByClassName('debug-panel');
                for (var i = 0; i < panels.length; i++) {
                    panels[i].style.display = 'none';
                }
                
                var tabs = document.getElementsByClassName('debug-tab');
                for (var i = 0; i < tabs.length; i++) {
                    tabs[i].classList.remove('active');
                }
                
                document.getElementById('panel-' + panelName).style.display = 'block';
                var activeTab = document.querySelector('.debug-tab[data-panel="' + panelName + '"]');
                if (activeTab) {
                    activeTab.classList.add('active');
                }
            }

            function initDebugBar() {
                // Update header stats
                document.getElementById('debug-time').textContent = debugBarData.performance.time + 'ms';
                document.getElementById('debug-memory').textContent = debugBarData.performance.memory;
                
                // Update overview
                document.getElementById('overview-time').textContent = debugBarData.performance.time + 'ms';
                document.getElementById('overview-memory').textContent = debugBarData.performance.memory;
                document.getElementById('overview-queries').textContent = debugBarData.queries.length;
                document.getElementById('overview-files').textContent = debugBarData.files.length;
                
                // Update badges
                document.getElementById('queries-count').textContent = debugBarData.queries.length;
                document.getElementById('logs-count').textContent = debugBarData.logs.length;
                
                // Render queries
                var queriesHtml = '';
                debugBarData.queries.forEach(function(query, index) {
                    queriesHtml += '<div class="debug-query">';
                    queriesHtml += '<div class="debug-query-sql">' + escapeHtml(query.sql) + '</div>';
                    queriesHtml += '<div class="debug-query-meta">';
                    queriesHtml += 'Time: ' + query.time + 'ms';
                    if (query.bindings && query.bindings.length > 0) {
                        queriesHtml += ' | Bindings: ' + escapeHtml(JSON.stringify(query.bindings));
                    }
                    queriesHtml += '</div></div>';
                });
                document.getElementById('queries-list').innerHTML = queriesHtml || '<p style="color: #888;">No queries executed</p>';
                
                // Render timeline
                var timelineHtml = '';
                debugBarData.timeline.forEach(function(item) {
                    timelineHtml += '<div class="debug-timeline-item">';
                    timelineHtml += '<div>' + escapeHtml(item.label) + '</div>';
                    timelineHtml += '<div style="color: #4ec9b0;">' + item.duration + 'ms</div>';
                    timelineHtml += '</div>';
                });
                document.getElementById('timeline-list').innerHTML = timelineHtml || '<p style="color: #888;">No timeline data</p>';
                
                // Render request info
                var requestHtml = '<div style="display: grid; gap: 15px;">';
                requestHtml += '<div><strong>Method:</strong> ' + debugBarData.request.method + '</div>';
                requestHtml += '<div><strong>URI:</strong> ' + escapeHtml(debugBarData.request.uri) + '</div>';
                requestHtml += '<div><strong>IP:</strong> ' + debugBarData.request.ip + '</div>';
                requestHtml += '</div>';
                document.getElementById('request-info').innerHTML = requestHtml;
                
                // Render logs
                var logsHtml = '';
                debugBarData.logs.forEach(function(log) {
                    logsHtml += '<div class="debug-log-item debug-log-' + log.level + '">';
                    logsHtml += '[' + log.level.toUpperCase() + '] ' + escapeHtml(log.message);
                    logsHtml += '</div>';
                });
                document.getElementById('logs-list').innerHTML = logsHtml || '<p style="color: #888;">No logs recorded</p>';
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDebugBar);
            } else {
                initDebugBar();
            }
            </script>
            HTML;
    }
}
