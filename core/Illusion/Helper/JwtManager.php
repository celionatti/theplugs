<?php

declare(strict_types=1);

namespace Plugs\Illusion\Helper;

use Plugs\Authentication\AuthConfig;
use Plugs\Authentication\Interface\UserInterface;

class JwtManager
{
    private AuthConfig $config;

    public function __construct(AuthConfig $config)
    {
        $this->config = $config;
    }

    public function createToken(UserInterface $user): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'iat' => time(),
            'exp' => time() + $this->config->jwtExpiry
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $this->config->jwtSecret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $header . '.' . $payload, $this->config->jwtSecret, true);
        $expectedBase64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));

        if (!hash_equals($expectedBase64Signature, $signature)) {
            return null;
        }

        // Decode payload
        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);

        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null;
        }

        return $payloadData;
    }
}