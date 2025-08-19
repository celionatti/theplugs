<?php

declare(strict_types=1);

namespace Illusion\Mailer;

use InvalidArgumentException;
use Illusion\Mailer\MailMessage;
use Illusion\Mailer\Drivers\SmtpDriver;
use Illusion\Mailer\Drivers\NativeDriver;
use Illusion\Mailer\Contracts\MailDriverInterface;
use Illusion\Mailer\Templates\SimpleTemplateRenderer;
use Illusion\Mailer\Contracts\TemplateRendererInterface;

class MailManager
{
    private array $config;
    private array $drivers = [];
    private TemplateRendererInterface $templateRenderer;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->templateRenderer = new SimpleTemplateRenderer($config['templates']['path'] ?? 'views/emails');
    }
    
    public function to(string $email, ?string $name = null): MailMessage
    {
        $mailMessage = new MailMessage($this, $this->templateRenderer);
        $mailMessage->to($email, $name);
        return $mailMessage;
        // return new MailMessage($this, $this->templateRenderer)->to($email, $name);
    }
    
    public function driver(?string $name = null): MailDriverInterface
    {
        $name = $name ?? $this->config['default'];
        
        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }
        
        return $this->drivers[$name];
    }
    
    private function createDriver(string $name): MailDriverInterface
    {
        if (!isset($this->config['drivers'][$name])) {
            throw new InvalidArgumentException("Mail driver [{$name}] is not configured.");
        }
        
        $config = $this->config['drivers'][$name];
        
        return match ($name) {
            'smtp' => new SmtpDriver($config),
            'native' => new NativeDriver($config),
            default => throw new InvalidArgumentException("Unsupported mail driver [{$name}].")
        };
    }
}