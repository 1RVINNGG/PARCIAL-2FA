<?php
declare(strict_types=1);

/** Fase 1 del login: usuario + contraseña (+ bloqueo por fuerza bruta y bitácora). */
final class ValidacionLogin
{
    private ?array $fila = null;
    private string $motivo = '';

    public function __construct(
        private UsuarioRepository $repo,
        private HashInterface $hasher,
        private string $login,
        private string $clave,
        private string $ip,
        private int $maxFallos,
        private int $ventanaMin
    ) {}

    public function autenticar(): bool
    {
        if ($this->login === '' || $this->clave === '') {
            $this->motivo = 'Ingrese usuario y contraseña.';
            return false;
        }
        if ($this->repo->contarFallosRecientes($this->login, $this->ventanaMin) >= $this->maxFallos) {
            $this->motivo = "Cuenta bloqueada temporalmente ({$this->ventanaMin} min) por demasiados intentos.";
            return false;
        }
        $this->fila = $this->repo->buscarPorLogin($this->login);
        // Se verifica siempre (hash ficticio si no existe) para no revelar usuarios por tiempo de respuesta
        $hash = $this->fila['HashMagic'] ?? '$2y$12$usesomesillystringforsaleOabcdefghijklmnopqrstuvwxyz12';
        $ok = $this->hasher->validar($this->clave, $hash) && $this->fila !== null;

        $this->repo->registrarIntento($this->login, $this->ip, 'PASSWORD', $ok);
        if (!$ok) {
            $this->motivo = 'Usuario o contraseña incorrectos.';
            $this->fila = null;
            return false;
        }
        if ($this->hasher->necesitaRehash($this->fila['HashMagic'])) {
            $this->repo->actualizarHash((int)$this->fila['id'], $this->hasher->generar($this->clave));
        }
        return true;
    }

    public function getUsuario(): ?array { return $this->fila; }
    public function getMotivo(): string  { return $this->motivo; }
}
