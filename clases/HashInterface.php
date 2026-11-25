<?php
declare(strict_types=1);

/** Contrato para generar y validar hashes de contraseñas. */
interface HashInterface
{
    public function nombre(): string;
    public function generar(string $texto): string;
    public function validar(string $texto, string $hash): bool;
    public function necesitaRehash(string $hash): bool;
}
