<?php

declare(strict_types=1);

namespace Plugs\View\Debug;

use Plugs\View\View;
use Plugs\View\ViewFinder;

class ViewDebugHelper
{
    /**
     * Debug view finding issues.
     */
    public static function debugViewFinding(string $viewName): array
    {
        $finder = View::finder();
        $debug = [
            'view_name' => $viewName,
            'normalized_name' => str_replace('.', '/', $viewName),
            'paths' => $finder->getPaths(),
            'extensions' => $finder->getExtensions(),
            'searched_locations' => [],
            'found' => false,
            'found_path' => null,
            'directory_contents' => []
        ];

        // Show what paths are being searched
        $normalizedName = str_replace('.', '/', $viewName);
        
        foreach ($finder->getPaths() as $path) {
            $debug['directory_contents'][$path] = [];
            
            if (is_dir($path)) {
                $debug['directory_contents'][$path] = self::getDirectoryContents($path);
                
                foreach ($finder->getExtensions() as $extension) {
                    $fullPath = $path . '/' . $normalizedName . $extension;
                    $debug['searched_locations'][] = $fullPath;
                    
                    if (file_exists($fullPath)) {
                        $debug['found'] = true;
                        $debug['found_path'] = $fullPath;
                        break 2;
                    }
                }
            } else {
                $debug['directory_contents'][$path] = 'Directory does not exist';
            }
        }

        return $debug;
    }

    /**
     * Get directory contents recursively.
     */
    protected static function getDirectoryContents(string $path, int $maxDepth = 2): array
    {
        if ($maxDepth <= 0 || !is_dir($path)) {
            return [];
        }

        $contents = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            
            if (is_dir($fullPath)) {
                $contents[$item . '/'] = self::getDirectoryContents($fullPath, $maxDepth - 1);
            } else {
                $contents[] = $item;
            }
        }

        return $contents;
    }

    /**
     * Print debug information in a readable format.
     */
    public static function printViewDebug(string $viewName): void
    {
        $debug = self::debugViewFinding($viewName);
        
        echo "<pre>";
        echo "=== VIEW DEBUG INFO ===\n";
        echo "View Name: {$debug['view_name']}\n";
        echo "Normalized: {$debug['normalized_name']}\n";
        echo "Found: " . ($debug['found'] ? 'YES' : 'NO') . "\n";
        
        if ($debug['found_path']) {
            echo "Found at: {$debug['found_path']}\n";
        }
        
        echo "\n--- Search Paths ---\n";
        foreach ($debug['paths'] as $path) {
            echo "- $path\n";
        }
        
        echo "\n--- Extensions ---\n";
        foreach ($debug['extensions'] as $ext) {
            echo "- $ext\n";
        }
        
        echo "\n--- Searched Locations ---\n";
        foreach ($debug['searched_locations'] as $location) {
            $exists = file_exists($location) ? '[EXISTS]' : '[NOT FOUND]';
            echo "$exists $location\n";
        }
        
        echo "\n--- Directory Contents ---\n";
        foreach ($debug['directory_contents'] as $path => $contents) {
            echo "$path:\n";
            if (is_array($contents)) {
                self::printArrayContents($contents, 1);
            } else {
                echo "  $contents\n";
            }
        }
        echo "</pre>";
    }

    /**
     * Helper to print array contents with indentation.
     */
    protected static function printArrayContents(array $contents, int $indent = 0): void
    {
        $spacing = str_repeat('  ', $indent);
        
        foreach ($contents as $key => $value) {
            if (is_array($value)) {
                echo "$spacing$key\n";
                self::printArrayContents($value, $indent + 1);
            } else {
                echo "$spacing$value\n";
            }
        }
    }

    /**
     * Test if view system is properly configured.
     */
    public static function testViewSystem(): array
    {
        $results = [
            'finder_configured' => false,
            'engine_resolver_configured' => false,
            'compiler_configured' => false,
            'paths_exist' => [],
            'extensions_registered' => [],
            'engines_registered' => []
        ];

        try {
            $finder = View::finder();
            $results['finder_configured'] = true;
            $results['extensions_registered'] = $finder->getExtensions();
            
            foreach ($finder->getPaths() as $path) {
                $results['paths_exist'][$path] = is_dir($path);
            }
        } catch (\Exception $e) {
            $results['finder_error'] = $e->getMessage();
        }

        try {
            $engineResolver = View::engines();
            $results['engine_resolver_configured'] = true;
            $results['engines_registered'] = $engineResolver->getEngines();
        } catch (\Exception $e) {
            $results['engine_resolver_error'] = $e->getMessage();
        }

        try {
            $compiler = View::compiler();
            $results['compiler_configured'] = true;
        } catch (\Exception $e) {
            $results['compiler_error'] = $e->getMessage();
        }

        return $results;
    }
}