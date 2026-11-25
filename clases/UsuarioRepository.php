<?php
declare(strict_types=1);

/** Único lugar que habla con las tablas (consultas preparadas, sin concatenar datos). */
final class UsuarioRepository
{
    public function __construct(private PDO $pdo) {}

    public function existeCorreo(string $correo): bool
    {
        return $this->existe('Correo', $correo);
    }

    public function existeUsuario(string $usuario): bool
    {
        return $this->existe('Usuario', $usuario);
    }

    private function existe(string $columna, string $valor): bool
    {
        // $columna solo viene de las dos llamadas de arriba (lista cerrada)
        $sql = "SELECT 1 FROM usuarios WHERE $columna = :v LIMIT 1";
        $st = $this->pdo->prepare($sql);
        $st->execute([':v' => $valor]);
        return (bool)$st->fetchColumn();
    }

    public function crear(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO usuarios (Nombre, Apellido, Correo, Usuario, HashMagic, Sexo)
             VALUES (:nombre, :apellido, :correo, :usuario, :hash, :sexo)'
        );
        $st->execute([
            ':nombre' => $d['nombre'], ':apellido' => $d['apellido'], ':correo' => $d['correo'],
            ':usuario' => $d['usuario'], ':hash' => $d['hash'], ':sexo' => $d['sexo'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Busca por nombre de usuario O por correo. */
    public function buscarPorLogin(string $login): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM usuarios WHERE Usuario = :u OR Correo = :c LIMIT 1');
        $st->execute([':u' => $login, ':c' => $login]);
        return $st->fetch() ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function guardarSecreto(int $id, string $secreto): void
    {
        $st = $this->pdo->prepare('UPDATE usuarios SET secret_2fa = :s WHERE id = :id AND confirmado_2fa = 0');
        $st->execute([':s' => $secreto, ':id' => $id]);
    }

    public function confirmar2fa(int $id): void
    {
        $st = $this->pdo->prepare('UPDATE usuarios SET confirmado_2fa = 1 WHERE id = :id');
        $st->execute([':id' => $id]);
    }

    public function actualizarHash(int $id, string $hash): void
    {
        $st = $this->pdo->prepare('UPDATE usuarios SET HashMagic = :h WHERE id = :id');
        $st->execute([':h' => $hash, ':id' => $id]);
    }

    public function registrarIntento(string $usuario, string $ip, string $etapa, bool $exitoso): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO intentos_login (Usuario, ipRemoto, etapa, exitoso) VALUES (:u, :ip, :e, :x)'
        );
        $st->execute([':u' => $usuario, ':ip' => $ip, ':e' => $etapa, ':x' => $exitoso ? 1 : 0]);
    }

    public function contarFallosRecientes(string $usuario, int $minutos): int
    {
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) FROM intentos_login
             WHERE Usuario = :u AND exitoso = 0 AND etapa = 'PASSWORD'
               AND fecha > (NOW() - INTERVAL $minutos MINUTE)"
        );
        $st->execute([':u' => $usuario]);
        return (int)$st->fetchColumn();
    }

    /** Para el panel: nunca se seleccionan HashMagic ni secret_2fa. */
    public function ultimosUsuarios(int $limite = 10): array
    {
        $st = $this->pdo->query(
            'SELECT id, Nombre, Apellido, Correo, Usuario, Sexo, confirmado_2fa, fecha_registro
             FROM usuarios ORDER BY id DESC LIMIT ' . (int)$limite
        );
        return $st->fetchAll();
    }

    public function ultimosIntentos(int $limite = 10): array
    {
        $st = $this->pdo->query(
            'SELECT id, Usuario, ipRemoto, etapa, exitoso, fecha
             FROM intentos_login ORDER BY id DESC LIMIT ' . (int)$limite
        );
        return $st->fetchAll();
    }
}
