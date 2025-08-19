<?php

declare(strict_types=1);

namespace Illusion\Mailer\Drivers;

use RuntimeException;
use Illusion\Mailer\MailEnvelope;
use Illusion\Mailer\Contracts\MailDriverInterface;

class SmtpDriver implements MailDriverInterface
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'auth' => true,
            'timeout' => 30
        ], $config);
    }
    
    public function send(MailEnvelope $envelope): bool
    {
        try {
            $socket = $this->connect();
            $this->authenticate($socket);
            $this->sendMessage($socket, $envelope);
            $this->disconnect($socket);
            
            return true;
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to send email via SMTP: " . $e->getMessage(), 0, $e);
        }
    }
    
    private function connect()
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $timeout = $this->config['timeout'];
        
        if ($this->config['encryption'] === 'ssl') {
            $host = 'ssl://' . $host;
        }
        
        $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!$socket) {
            throw new RuntimeException("Could not connect to SMTP server: {$errstr} ({$errno})");
        }
        
        $this->readResponse($socket, 220);
        
        // Send EHLO
        $this->sendCommand($socket, 'EHLO ' . gethostname());
        $this->readResponse($socket, 250);
        
        // Start TLS if required
        if ($this->config['encryption'] === 'tls') {
            $this->sendCommand($socket, 'STARTTLS');
            $this->readResponse($socket, 220);
            
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Could not enable TLS encryption');
            }
            
            // Send EHLO again after TLS
            $this->sendCommand($socket, 'EHLO ' . gethostname());
            $this->readResponse($socket, 250);
        }
        
        return $socket;
    }
    
    private function authenticate($socket): void
    {
        if (!$this->config['auth'] || empty($this->config['username'])) {
            return;
        }
        
        $this->sendCommand($socket, 'AUTH LOGIN');
        $this->readResponse($socket, 334);
        
        $this->sendCommand($socket, base64_encode($this->config['username']));
        $this->readResponse($socket, 334);
        
        $this->sendCommand($socket, base64_encode($this->config['password']));
        $this->readResponse($socket, 235);
    }
    
    private function sendMessage($socket, MailEnvelope $envelope): void
    {
        // MAIL FROM
        $from = $envelope->getFrom();
        $this->sendCommand($socket, 'MAIL FROM:<' . $from['email'] . '>');
        $this->readResponse($socket, 250);
        
        // RCPT TO
        foreach (array_merge($envelope->getTo(), $envelope->getCc(), $envelope->getBcc()) as $recipient) {
            $this->sendCommand($socket, 'RCPT TO:<' . $recipient['email'] . '>');
            $this->readResponse($socket, 250);
        }
        
        // DATA
        $this->sendCommand($socket, 'DATA');
        $this->readResponse($socket, 354);
        
        // Send headers and body
        $message = $this->buildMessage($envelope);
        $this->sendCommand($socket, $message . "\r\n.");
        $this->readResponse($socket, 250);
    }
    
    private function disconnect($socket): void
    {
        $this->sendCommand($socket, 'QUIT');
        fclose($socket);
    }
    
    private function sendCommand($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }
    
    private function readResponse($socket, int $expectedCode): string
    {
        $response = fgets($socket, 512);
        $code = (int) substr($response, 0, 3);
        
        if ($code !== $expectedCode) {
            throw new RuntimeException("SMTP Error: Expected {$expectedCode}, got {$code} - {$response}");
        }
        
        return $response;
    }
    
    private function buildMessage(MailEnvelope $envelope): string
    {
        $headers = $this->buildHeaders($envelope);
        $body = $this->buildBody($envelope);
        
        return $headers . "\r\n" . $body;
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
        
        // To
        $toList = [];
        foreach ($envelope->getTo() as $recipient) {
            $toList[] = $recipient['name'] ? "{$recipient['name']} <{$recipient['email']}>" : $recipient['email'];
        }
        $headers[] = "To: " . implode(', ', $toList);
        
        // CC
        if (!empty($envelope->getCc())) {
            $ccList = [];
            foreach ($envelope->getCc() as $recipient) {
                $ccList[] = $recipient['name'] ? "{$recipient['name']} <{$recipient['email']}>" : $recipient['email'];
            }
            $headers[] = "Cc: " . implode(', ', $ccList);
        }
        
        // Reply-To
        $replyTo = $envelope->getReplyTo();
        if (!empty($replyTo)) {
            $replyToHeader = $replyTo['name'] ? "{$replyTo['name']} <{$replyTo['email']}>" : $replyTo['email'];
            $headers[] = "Reply-To: {$replyToHeader}";
        }
        
        // Subject
        $headers[] = "Subject: " . $envelope->getSubject();
        
        // Date
        $headers[] = "Date: " . date('r');
        
        // MIME headers
        $boundary = uniqid('boundary_');
        $headers[] = "MIME-Version: 1.0";
        
        if ($envelope->hasHtml() && $envelope->hasText()) {
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        } elseif ($envelope->hasHtml()) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        return implode("\r\n", $headers);
    }
    
    private function buildBody(MailEnvelope $envelope): string
    {
        if ($envelope->hasHtml() && $envelope->hasText()) {
            return $this->buildMultipartBody($envelope);
        } elseif ($envelope->hasHtml()) {
            return $envelope->getHtmlBody();
        } else {
            return $envelope->getTextBody();
        }
    }
    
    private function buildMultipartBody(MailEnvelope $envelope): string
    {
        $boundary = uniqid('boundary_');
        $body = "";
        
        if ($envelope->hasText()) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $envelope->getTextBody() . "\r\n\r\n";
        }
        
        if ($envelope->hasHtml()) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $envelope->getHtmlBody() . "\r\n\r\n";
        }
        
        $body .= "--{$boundary}--\r\n";
        
        return $body;
    }
}