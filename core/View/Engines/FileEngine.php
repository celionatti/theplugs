<?php

declare(strict_types=1);

namespace Plugs\View\Engines;

use Plugs\Exceptions\View\ViewException;

class FileEngine implements EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     */
    public function get(string $path, array $data = []): string
    {
        $contents = file_get_contents($path);
        
        if ($contents === false) {
            throw new ViewException("Unable to read file: {$path}");
        }

        // For static files, we can optionally do simple variable replacement
        if (!empty($data)) {
            $contents = $this->replaceVariables($contents, $data);
        }

        return $contents;
    }

    /**
     * Simple variable replacement for static files.
     * This replaces {{variable}} with actual values.
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $placeholder = '{{' . $key . '}}';
                $content = str_replace($placeholder, (string) $value, $content);
            }
        }

        return $content;
    }
}