<?php
declare(strict_types=1);

final class BcryptHash implements HashInterface
{
    public function __construct(private int $cost = 12) {}

    public function nombre(): string { return 'bcrypt'; }

    public function generar(string $texto): string
    {
        return password_hash($texto, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }

    public function validar(string $texto, string $hash): bool
    {
        return password_verify($texto, $hash);
    }

    public function necesitaRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
    }
}
