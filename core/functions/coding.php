<?php

declare(strict_types=1);

/**
 * This file is part of ThePlugs.
 * It is subject to the license terms in the LICENSE file found in the top-level directory of this distribution.
 * It is also available at my website: https://theplugs.com/license
 * 
 * Enhanced with:
 * - More secure Base32 implementation
 * - Added Base64 URL-safe encoding
 * - Added AES-256-GCM encryption
 * - Added SHA-3 hashing
 * - Input validation
 * - Constant-time comparison
 * - Error handling
 */

if (!function_exists('base32_encode')) {
    /**
     * Secure Base32 encoding with input validation
     * 
     * @param string $input The input string to encode
     * @return string Base32 encoded string
     * @throws InvalidArgumentException If input is not a string
     */
    function base32_encode(string $input): string
    {
        if (!is_string($input)) {
            throw new InvalidArgumentException('Input must be a string');
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $v <<= 8;
            $v |= ord($input[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[($v >> $vbits) & 31];
            }
        }

        if ($vbits > 0) {
            $v <<= 5 - $vbits;
            $output .= $alphabet[$v & 31];
        }

        return $output;
    }
}

if (!function_exists('base32_decode')) {
    /**
     * Secure Base32 decoding with input validation
     * 
     * @param string $input The Base32 string to decode
     * @return string Decoded string
     * @throws InvalidArgumentException If input contains invalid Base32 characters
     */
    function base32_decode(string $input): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        $input = strtoupper($input);
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];
            $pos = strpos($alphabet, $char);
            
            if ($pos === false) {
                throw new InvalidArgumentException("Invalid Base32 character: $char");
            }

            $v <<= 5;
            $v |= $pos;
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }

        return $output;
    }
}

if (!function_exists('base64url_encode')) {
    /**
     * URL-safe Base64 encoding
     * 
     * @param string $input The input string to encode
     * @return string URL-safe Base64 encoded string
     */
    function base64url_encode(string $input): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($input));
    }
}

if (!function_exists('base64url_decode')) {
    /**
     * URL-safe Base64 decoding
     * 
     * @param string $input The URL-safe Base64 string to decode
     * @return string Decoded string
     * @throws InvalidArgumentException If input is not valid Base64
     */
    function base64url_decode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        
        $decoded = base64_decode(str_replace(['-', '_'], ['+', '/'], $input), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid Base64URL input');
        }
        
        return $decoded;
    }
}

if (!function_exists('aes_encrypt')) {
    /**
     * AES-256-GCM encryption
     * 
     * @param string $plaintext The text to encrypt
     * @param string $key Encryption key (must be 32 bytes)
     * @return string Encrypted data (IV + ciphertext + tag)
     * @throws InvalidArgumentException If key is invalid
     */
    function aes_encrypt(string $plaintext, string $key): string
    {
        if (strlen($key) !== 32) {
            throw new InvalidArgumentException('Key must be 32 bytes long');
        }

        $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed');
        }

        return $iv . $ciphertext . $tag;
    }
}

if (!function_exists('aes_decrypt')) {
    /**
     * AES-256-GCM decryption
     * 
     * @param string $ciphertext The encrypted data (IV + ciphertext + tag)
     * @param string $key Decryption key (must be 32 bytes)
     * @return string Decrypted plaintext
     * @throws InvalidArgumentException If decryption fails
     */
    function aes_decrypt(string $ciphertext, string $key): string
    {
        if (strlen($key) !== 32) {
            throw new InvalidArgumentException('Key must be 32 bytes long');
        }

        $iv_len = openssl_cipher_iv_length('aes-256-gcm');
        $iv = substr($ciphertext, 0, $iv_len);
        $tag = substr($ciphertext, -16);
        $ciphertext = substr($ciphertext, $iv_len, -16);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed');
        }

        return $plaintext;
    }
}

if (!function_exists('secure_compare')) {
    /**
     * Constant-time string comparison
     * 
     * @param string $a First string
     * @param string $b Second string
     * @return bool True if strings are equal
     */
    function secure_compare(string $a, string $b): bool
    {
        if (!is_string($a) || !is_string($b)) {
            return false;
        }

        $len = strlen($a);
        if ($len !== strlen($b)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $len; $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result === 0;
    }
}

if (!function_exists('sha3_hash')) {
    /**
     * SHA-3 hashing (256-bit by default)
     * 
     * @param string $input The input string
     * @param int $length The hash length (224, 256, 384, or 512)
     * @return string The hash
     * @throws InvalidArgumentException If invalid length is specified
     */
    function sha3_hash(string $input, int $length = 256): string
    {
        $valid_lengths = [224, 256, 384, 512];
        if (!in_array($length, $valid_lengths, true)) {
            throw new InvalidArgumentException('Invalid hash length');
        }

        return hash("sha3-$length", $input, true);
    }
}

if (!function_exists('generate_crypto_key')) {
    /**
     * Generate a cryptographically secure random key
     * 
     * @param int $length The key length in bytes
     * @return string Random bytes
     * @throws RuntimeException If generation fails
     */
    function generate_crypto_key(int $length = 32): string
    {
        if ($length < 16) {
            throw new InvalidArgumentException('Key length must be at least 16 bytes');
        }

        $key = random_bytes($length);
        if ($key === false) {
            throw new RuntimeException('Could not generate random key');
        }

        return $key;
    }
}