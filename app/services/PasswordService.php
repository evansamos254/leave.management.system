<?php

class PasswordService
{
    private const ITERATIONS = 120000;
    private const ALGORITHM = 'sha256';

    public static function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$';
        $password = '';

        for ($i = 0; $i < 12; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }

    public static function make(string $password): string
    {
        $salt = bin2hex(random_bytes(16));
        $hash = hash_pbkdf2(self::ALGORITHM, $password, $salt, self::ITERATIONS, 64);

        return sprintf('pbkdf2_%s$%d$%s$%s', self::ALGORITHM, self::ITERATIONS, $salt, $hash);
    }

    public static function verify(string $password, string $storedHash): bool
    {
        $parts = explode('$', $storedHash);

        if (count($parts) !== 4 || $parts[0] !== 'pbkdf2_sha256') {
            return false;
        }

        [$scheme, $iterations, $salt, $hash] = $parts;
        $candidate = hash_pbkdf2(self::ALGORITHM, $password, $salt, (int) $iterations, strlen($hash));

        return hash_equals($hash, $candidate);
    }
}
