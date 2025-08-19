<?php

declare(strict_types=1);

namespace Illusion\Mailer;

use Illusion\Mailer\MailManager;

class Mail
{
    private static ?MailManager $instance = null;
    
    public static function getInstance(): MailManager
    {
        if (self::$instance === null) {
            // Default configuration - you can customize this
            $config = [
                'default' => 'smtp',
                'drivers' => [
                    'smtp' => [
                        'host' => 'localhost',
                        'port' => 587,
                        'username' => '',
                        'password' => '',
                        'encryption' => 'tls',
                        'auth' => true
                    ],
                    'native' => []
                ],
                'templates' => [
                    'path' => 'views/emails'
                ]
            ];
            
            self::$instance = new MailManager($config);
        }
        
        return self::$instance;
    }
    
    public static function to(string $email, ?string $name = null): MailMessage
    {
        return self::getInstance()->to($email, $name);
    }
    
    public static function configure(array $config): void
    {
        self::$instance = new MailManager($config);
    }
}