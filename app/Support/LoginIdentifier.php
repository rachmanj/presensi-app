<?php

namespace App\Support;

class LoginIdentifier
{
    public static function credentialField(string $identifier): string
    {
        return str_contains($identifier, '@') ? 'email' : 'username';
    }

    /**
     * @return array{email?: string, username?: string, password: string}
     */
    public static function resolveCredentials(string $identifier, string $password): array
    {
        return [
            self::credentialField($identifier) => $identifier,
            'password' => $password,
        ];
    }
}
