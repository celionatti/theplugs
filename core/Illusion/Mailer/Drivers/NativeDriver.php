<?php

declare(strict_types=1);

namespace Illusion\Mailer\Drivers;

use Illusion\Mailer\MailEnvelope;
use Illusion\Mailer\Contracts\MailDriverInterface;

class NativeDriver implements MailDriverInterface
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function send(MailEnvelope $envelope): bool
    {
        $to = $this->buildAddressList($envelope->getTo());
        $subject = $envelope->getSubject();
        $message = $envelope->hasHtml() ? $envelope->getHtmlBody() : $envelope->getTextBody();
        $headers = $this->buildHeaders($envelope);
        
        return mail($to, $subject, $message, $headers);
    }
    
    private function buildAddressList(array $addresses): string
    {
        $list = [];
        foreach ($addresses as $address) {
            $list[] = $address['name'] ? "{$address['name']} <{$address['email']}>" : $address['email'];
        }
        return implode(', ', $list);
    }
    
    private function buildHeaders(MailEnvelope $envelope): string
    {
        $headers = [];
        
        // From
        $from = $envelope->getFrom();
        if (!empty($from)) {
            $fromHeader = $from['name'] ? "{$from['name']} <{$from['email']}>" : $from['email'];
            $headers[] = "From: {$fromHeader}";
        }
        
        // CC
        if (!empty($envelope->getCc())) {
            $headers[] = "Cc: " . $this->buildAddressList($envelope->getCc());
        }
        
        // BCC
        if (!empty($envelope->getBcc())) {
            $headers[] = "Bcc: " . $this->buildAddressList($envelope->getBcc());
        }
        
        // Reply-To
        $replyTo = $envelope->getReplyTo();
        if (!empty($replyTo)) {
            $replyToHeader = $replyTo['name'] ? "{$replyTo['name']} <{$replyTo['email']}>" : $replyTo['email'];
            $headers[] = "Reply-To: {$replyToHeader}";
        }
        
        // Content type
        if ($envelope->hasHtml()) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headers[] = "MIME-Version: 1.0";
        
        return implode("\r\n", $headers);
    }
}