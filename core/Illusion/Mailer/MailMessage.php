<?php

declare(strict_types=1);

namespace Illusion\Mailer;

use InvalidArgumentException;
use Illusion\Mailer\MailManager;
use Illusion\Mailer\MailEnvelope;
use Illusion\Mailer\Contracts\TemplateRendererInterface;

class MailMessage
{
    private MailManager $manager;
    private TemplateRendererInterface $templateRenderer;
    
    private array $to = [];
    private array $from = [];
    private array $cc = [];
    private array $bcc = [];
    private array $replyTo = [];
    private string $subject = '';
    private string $htmlBody = '';
    private string $textBody = '';
    private array $attachments = [];
    private ?string $driver = null;
    
    public function __construct(MailManager $manager, TemplateRendererInterface $templateRenderer)
    {
        $this->manager = $manager;
        $this->templateRenderer = $templateRenderer;
    }
    
    public function to(string $email, ?string $name = null): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }
    
    public function from(string $email, ?string $name = null): self
    {
        $this->from = ['email' => $email, 'name' => $name];
        return $this;
    }
    
    public function cc(string $email, ?string $name = null): self
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }
    
    public function bcc(string $email, ?string $name = null): self
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
    }
    
    public function replyTo(string $email, ?string $name = null): self
    {
        $this->replyTo = ['email' => $email, 'name' => $name];
        return $this;
    }
    
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }
    
    public function html(string $html): self
    {
        $this->htmlBody = $html;
        return $this;
    }
    
    public function text(string $text): self
    {
        $this->textBody = $text;
        return $this;
    }
    
    public function view(string $template, array $data = []): self
    {
        $this->htmlBody = $this->templateRenderer->render($template, $data);
        return $this;
    }
    
    public function textView(string $template, array $data = []): self
    {
        $this->textBody = $this->templateRenderer->render($template, $data);
        return $this;
    }
    
    public function attach(string $filePath, ?string $name = null, ?string $mimeType = null): self
    {
        $this->attachments[] = [
            'path' => $filePath,
            'name' => $name ?? basename($filePath),
            'mime' => $mimeType ?? mime_content_type($filePath)
        ];
        return $this;
    }
    
    public function driver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }
    
    public function send(): bool
    {
        $this->validate();
        
        $envelope = new MailEnvelope(
            $this->to,
            $this->from,
            $this->cc,
            $this->bcc,
            $this->replyTo,
            $this->subject,
            $this->htmlBody,
            $this->textBody,
            $this->attachments
        );
        
        return $this->manager->driver($this->driver)->send($envelope);
    }
    
    private function validate(): void
    {
        if (empty($this->to)) {
            throw new InvalidArgumentException('At least one recipient is required.');
        }
        
        if (empty($this->subject)) {
            throw new InvalidArgumentException('Subject is required.');
        }
        
        if (empty($this->htmlBody) && empty($this->textBody)) {
            throw new InvalidArgumentException('Message body is required.');
        }
    }
}