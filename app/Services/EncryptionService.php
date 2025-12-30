<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Encryption Service - Application-Level File Encryption
 * 
 * This service handles encryption and decryption of files at the application level.
 * Files are encrypted with AES-256-CBC before being stored, and the encryption key
 * is encrypted with Laravel's app key and stored in the database.
 */
class EncryptionService
{
    /**
     * Encryption cipher method
     */
    private const CIPHER = 'AES-256-CBC';

    /**
     * IV length for AES-256-CBC
     */
    private const IV_LENGTH = 16;

    /**
     * Key length for AES-256
     */
    private const KEY_LENGTH = 32;

    /**
     * Encrypt file content
     *
     * @param string $content Plaintext content
     * @return array{encrypted: string, key: string, iv: string}
     */
    public function encrypt(string $content): array
    {
        // Generate random encryption key (AES-256)
        $key = random_bytes(self::KEY_LENGTH);
        
        // Generate random IV
        $iv = random_bytes(self::IV_LENGTH);
        
        // Encrypt content
        $encrypted = openssl_encrypt(
            $content,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed: ' . openssl_error_string());
        }
        
        // Encrypt the key with Laravel's app key
        $encryptedKey = Crypt::encryptString(base64_encode($key));
        
        return [
            'encrypted' => $encrypted,
            'key' => $encryptedKey,
            'iv' => base64_encode($iv),
        ];
    }

    /**
     * Decrypt file content
     *
     * @param string $encryptedContent Encrypted content
     * @param string $encryptedKey Encrypted key (encrypted with Laravel app key)
     * @param string $iv Base64-encoded IV
     * @return string Plaintext content
     */
    public function decrypt(string $encryptedContent, string $encryptedKey, string $iv): string
    {
        // Decrypt the key with Laravel's app key
        $key = base64_decode(Crypt::decryptString($encryptedKey));
        
        // Decode IV
        $ivDecoded = base64_decode($iv);
        
        // Decrypt content
        $decrypted = openssl_decrypt(
            $encryptedContent,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $ivDecoded
        );
        
        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed: ' . openssl_error_string());
        }
        
        return $decrypted;
    }

    /**
     * Calculate SHA-256 checksum of content
     *
     * @param string $content
     * @return string
     */
    public function checksum(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Verify checksum
     *
     * @param string $content
     * @param string $expectedChecksum
     * @return bool
     */
    public function verifyChecksum(string $content, string $expectedChecksum): bool
    {
        $calculated = $this->checksum($content);
        return hash_equals($expectedChecksum, $calculated);
    }
}
