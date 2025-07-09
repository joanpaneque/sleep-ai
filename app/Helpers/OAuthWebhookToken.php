<?php

namespace App\Helpers;

use App\Models\Channel;

class OAuthWebhookToken
{
    /**
     * Generate a random token of 16 characters containing at least one uppercase letter,
     * one lowercase letter, and one number.
     *
     * @return string
     */
    public static function generateToken(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $allChars = $uppercase . $lowercase . $numbers;

        // Ensure we have at least one of each required type
        $token = '';
        $token .= $uppercase[random_int(0, strlen($uppercase) - 1)]; // At least one uppercase
        $token .= $lowercase[random_int(0, strlen($lowercase) - 1)]; // At least one lowercase
        $token .= $numbers[random_int(0, strlen($numbers) - 1)];     // At least one number

        // Fill the remaining 13 characters randomly
        for ($i = 3; $i < 16; $i++) {
            $token .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the token to randomize the position of required characters
        return str_shuffle($token);
    }

    /**
     * Check if a token is valid and unique.
     * Validates format requirements and database uniqueness.
     *
     * @param string $token
     * @return array Returns array with 'valid' boolean and 'errors' array
     */
    public static function checkToken(string $token): array
    {
        $errors = [];

        // Check if token exists in database
        $existsInDb = Channel::where('google_oauth_webhook_token', $token)->exists();
        if ($existsInDb) {
            $errors[] = 'Token already exists in database';
        }

        // Check format requirements
        if (!self::validateTokenFormat($token)) {
            $errors[] = 'Token must contain at least one uppercase letter, one lowercase letter, and one number';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate if token meets format requirements.
     *
     * @param string $token
     * @return bool
     */
    private static function validateTokenFormat(string $token): bool
    {
        // Check if token has at least one uppercase letter
        if (!preg_match('/[A-Z]/', $token)) {
            return false;
        }

        // Check if token has at least one lowercase letter
        if (!preg_match('/[a-z]/', $token)) {
            return false;
        }

        // Check if token has at least one number
        if (!preg_match('/[0-9]/', $token)) {
            return false;
        }

        return true;
    }
}
