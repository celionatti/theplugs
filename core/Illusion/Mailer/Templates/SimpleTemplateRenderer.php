<?php

declare(strict_types=1);

namespace Illusion\Mailer\Templates;

use RuntimeException;
use InvalidArgumentException;
use Illusion\Mailer\Contracts\TemplateRendererInterface;

class SimpleTemplateRenderer implements TemplateRendererInterface
{
    private string $templatePath;
    
    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/');
    }
    
    public function render(string $template, array $data = []): string
    {
        $templateFile = $this->resolveTemplatePath($template);
        
        if (!file_exists($templateFile)) {
            throw new InvalidArgumentException("Template file not found: {$templateFile}");
        }
        
        return $this->renderTemplate($templateFile, $data);
    }
    
    private function resolveTemplatePath(string $template): string
    {
        // Convert dot notation to file path (e.g., emails.welcome -> emails/welcome.php)
        $path = str_replace('.', '/', $template);
        
        // Try different extensions
        $extensions = ['.php', '.html', '.htm', '.plug.php'];
        
        foreach ($extensions as $ext) {
            $file = $this->templatePath . '/' . $path . $ext;
            if (file_exists($file)) {
                return $file;
            }
        }
        
        return $this->templatePath . '/' . $path . '.php';
    }
    
    private function renderTemplate(string $templateFile, array $data): string
    {
        // Extract data to variables and escape them
        $escapedData = $this->escapeData($data);
        extract($escapedData, EXTR_SKIP);
        
        // Provide helper functions
        $e = function($value) {
            return $this->escape($value);
        };
        
        $raw = function($value) {
            return $value;
        };
        
        ob_start();
        
        try {
            include $templateFile;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering template: " . $e->getMessage(), 0, $e);
        }
        
        return ob_get_clean();
    }
    
    private function escapeData(array $data): array
    {
        $escaped = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $escaped[$key] = $this->escape($value);
            } elseif (is_array($value)) {
                $escaped[$key] = $this->escapeData($value);
            } else {
                $escaped[$key] = $value;
            }
        }
        
        return $escaped;
    }
    
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}