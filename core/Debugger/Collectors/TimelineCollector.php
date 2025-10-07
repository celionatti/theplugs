<?php

declare(strict_types=1);

namespace Plugs\Debugger\Collectors;

use Plugs\Debugger\Collectors\CollectorInterface;

class TimelineCollector implements CollectorInterface
{
    private $measures = [];
    private $markers = [];
    
    public function startMeasure($name, $label = null)
    {
        $this->measures[$name] = [
            'label' => $label ?: $name,
            'start' => microtime(true),
        ];
    }
    
    public function stopMeasure($name)
    {
        if (isset($this->measures[$name])) {
            $this->measures[$name]['end'] = microtime(true);
            $this->measures[$name]['duration'] = round(
                ($this->measures[$name]['end'] - $this->measures[$name]['start']) * 1000,
                2
            );
        }
    }
    
    public function collect()
    {
        $timeline = [];
        foreach ($this->measures as $measure) {
            if (isset($measure['duration'])) {
                $timeline[] = [
                    'label' => $measure['label'],
                    'duration' => $measure['duration'],
                ];
            }
        }
        return $timeline;
    }
}