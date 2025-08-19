<?php

declare(strict_types=1);

namespace Illusion\Mailer;

class MailEnvelope
{
    public function __construct(
        private array $to,
        private array $from,
        private array $cc,
        private array $bcc,
        private array $replyTo,
        private string $subject,
        private string $htmlBody,
        private string $textBody,
        private array $attachments
    ) {}
    
    public function getTo(): array { return $this->to; }
    public function getFrom(): array { return $this->from; }
    public function getCc(): array { return $this->cc; }
    public function getBcc(): array { return $this->bcc; }
    public function getReplyTo(): array { return $this->replyTo; }
    public function getSubject(): string { return $this->subject; }
    public function getHtmlBody(): string { return $this->htmlBody; }
    public function getTextBody(): string { return $this->textBody; }
    public function getAttachments(): array { return $this->attachments; }
    public function hasHtml(): bool { return !empty($this->htmlBody); }
    public function hasText(): bool { return !empty($this->textBody); }
}