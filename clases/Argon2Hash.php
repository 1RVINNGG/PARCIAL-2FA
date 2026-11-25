<?php
declare(strict_types=1);

final class Argon2Hash implements HashInterface
{
    public static function disponible(): bool
    {
        return defined('PASSWORD_ARGON2ID');
    }

    public function nombre(): string { return 'argon2id'; }

    public function generar(string $texto): string
    {
        return password_hash($texto, PASSWORD_ARGON2ID);
    }

    public function validar(string $texto, string $hash): bool
    {
        return password_verify($texto, $hash);
    }

    public function necesitaRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }
}
