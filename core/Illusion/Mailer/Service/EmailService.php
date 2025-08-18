<?php

declare(strict_types=1);

namespace Illusion\Mailer\Service;

use RuntimeException;
use InvalidArgumentException;
use Plugs\Authentication\AuthConfig;
use Plugs\Authentication\Interface\UserInterface;

/**
 * Email service class with template support
 */
class EmailService
{
    private AuthConfig $config;
    private string $templatePath;
    private array $defaultTemplates = [
        'verification' => [
            'subject' => 'Verify Your Email Address',
            'template' => 'emails/verification.php',
            'text_template' => 'emails/verification.plug.php'
        ],
        'password_reset' => [
            'subject' => 'Password Reset Request',
            'template' => 'emails/password_reset.php',
            'text_template' => 'emails/password_reset.plug.php'
        ]
    ];

    public function __construct(AuthConfig $config, ?string $templatePath = null)
    {
        $this->config = $config;
        $this->templatePath = $templatePath ?? __DIR__ . '/templates/';
    }

    /**
     * Set custom template path
     */
    public function setTemplatePath(string $path): void
    {
        $this->templatePath = rtrim($path, '/') . '/';
    }

    /**
     * Override default templates
     */
    public function setTemplate(string $type, string $subject, string $htmlTemplate, ?string $textTemplate = null): void
    {
        $this->defaultTemplates[$type] = [
            'subject' => $subject,
            'template' => $htmlTemplate,
            'text_template' => $textTemplate
        ];
    }

    /**
     * Send verification email with token
     */
    public function sendVerificationEmail(UserInterface $user, string $token): bool
    {
        $template = $this->getTemplate('verification');
        $verificationUrl = $this->config->appUrl . "/verify-email?token=" . urlencode($token);
        
        $data = [
            'user' => $user,
            'token' => $token,
            'verificationUrl' => $verificationUrl,
            'appName' => $this->config->appName
        ];

        return $this->sendEmail(
            $user->getEmail(),
            $template['subject'],
            $this->renderTemplate($template['template'], $data),
            $template['text_template'] ? $this->renderTemplate($template['text_template'], $data) : null
        );
    }

    /**
     * Send password reset email with token
     */
    public function sendPasswordResetEmail(UserInterface $user, string $token): bool
    {
        $template = $this->getTemplate('password_reset');
        $resetUrl = $this->config->appUrl . "/reset-password?token=" . urlencode($token);
        
        $data = [
            'user' => $user,
            'token' => $token,
            'resetUrl' => $resetUrl,
            'appName' => $this->config->appName
        ];

        return $this->sendEmail(
            $user->getEmail(),
            $template['subject'],
            $this->renderTemplate($template['template'], $data),
            $template['text_template'] ? $this->renderTemplate($template['text_template'], $data) : null
        );
    }

    /**
     * Get template configuration
     */
    private function getTemplate(string $type): array
    {
        if (!isset($this->defaultTemplates[$type])) {
            throw new InvalidArgumentException("Unknown email template type: $type");
        }
        
        return $this->defaultTemplates[$type];
    }

    /**
     * Render email template with provided data
     */
    private function renderTemplate(string $templateFile, array $data): string
    {
        $fullPath = $this->templatePath . ltrim($templateFile, '/');
        
        if (!file_exists($fullPath)) {
            throw new RuntimeException("Email template not found: $fullPath");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $fullPath;
        return ob_get_clean();
    }

    /**
     * Send email using PHP mail() or your preferred mailer
     */
    private function sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        // If no text body provided, create one from HTML
        $textBody = $textBody ?? strip_tags($htmlBody);
        
        $headers = [
            'From' => $this->config->mailFrom,
            'Reply-To' => $this->config->mailReplyTo,
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8'
        ];

        if ($textBody) {
            // For multipart emails
            $boundary = uniqid('mp');
            $headers['Content-Type'] = "multipart/alternative; boundary=\"$boundary\"";
            
            $message = "--$boundary\r\n" .
                "Content-Type: text/plain; charset=\"utf-8\"\r\n" .
                "Content-Transfer-Encoding: 7bit\r\n\r\n" .
                $textBody . "\r\n\r\n" .
                "--$boundary\r\n" .
                "Content-Type: text/html; charset=\"utf-8\"\r\n" .
                "Content-Transfer-Encoding: 7bit\r\n\r\n" .
                $htmlBody . "\r\n\r\n" .
                "--$boundary--";
        } else {
            $message = $htmlBody;
        }

        $headerString = '';
        foreach ($headers as $name => $value) {
            $headerString .= "$name: $value\r\n";
        }

        return mail($to, $subject, $message, $headerString);
    }
}