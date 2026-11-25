<?php
declare(strict_types=1);

/** Usa cualquier implementación de HashInterface (polimorfismo / inyección de dependencias). */
final class ServicioHash
{
    public function __construct(private HashInterface $algoritmo) {}

    public function generar(string $texto): string
    {
        return $this->algoritmo->generar($texto);
    }

    public function validar(string $texto, string $hash): bool
    {
        return $this->algoritmo->validar($texto, $hash);
    }

    /** @return array{algoritmo:string, info:array} */
    public function describir(string $hash): array
    {
        return ['algoritmo' => $this->algoritmo->nombre(), 'info' => password_get_info($hash)];
    }

    public static function crear(string $tipo, int $cost = 12): self
    {
        return new self($tipo === 'argon2id' && Argon2Hash::disponible() ? new Argon2Hash() : new BcryptHash($cost));
    }
}
