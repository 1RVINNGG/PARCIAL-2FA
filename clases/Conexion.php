<?php
declare(strict_types=1);

/** Crea conexiones PDO con usuarios de privilegios mínimos (nunca root). */
final class Conexion
{
    /** @var array<string,PDO> */
    private static array $pool = [];

    public static function obtener(string $perfil = 'app'): PDO
    {
        if (isset(self::$pool[$perfil])) {
            return self::$pool[$perfil];
        }
        $cfg = require __DIR__ . '/../config/config.php';
        if (!isset($cfg['perfiles'][$perfil])) {
            throw new InvalidArgumentException('Perfil de BD desconocido');
        }
        $db  = $cfg['db'];
        $dsn = "mysql:host={$db['host']};port={$db['puerto']};dbname={$db['nombre']};charset={$db['charset']}";
        $pdo = new PDO($dsn, $cfg['perfiles'][$perfil]['usuario'], $cfg['perfiles'][$perfil]['clave'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return self::$pool[$perfil] = $pdo;
    }
}
