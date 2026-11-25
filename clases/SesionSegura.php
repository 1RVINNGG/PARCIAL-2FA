<?php
declare(strict_types=1);

/**
 * Maneja DOS sesiones independientes:
 *  - PREAUTH_2FA : sesión previa (usuario+clave correctos, falta el código 2FA).
 *  - APP_SESION  : sesión definitiva, creada SOLO cuando el código TOTP fue validado.
 */
final class SesionSegura
{
    public const PRE = 'PREAUTH_2FA';
    public const APP = 'APP_SESION';

    public static function abrir(string $nombre): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (session_name() === $nombre) {
                return;
            }
            session_write_close();
        }
        ini_set('session.use_strict_mode', '1');
        session_name($nombre);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_id($_COOKIE[$nombre] ?? '');
        session_start();
    }

    public static function destruir(string $nombre): void
    {
        self::abrir($nombre);
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie($nombre, '', [
            'expires' => time() - 3600, 'path' => $params['path'],
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    /** Fase 1 superada: se guarda en la sesión previa quién es (aún sin acceso al sistema). */
    public static function iniciarPaso1(array $usuario): void
    {
        self::abrir(self::PRE);
        session_regenerate_id(true);
        $_SESSION['paso1'] = [
            'id' => (int)$usuario['id'], 'usuario' => $usuario['Usuario'],
            'correo' => $usuario['Correo'], 'hora' => time(), 'fallos_2fa' => 0,
        ];
        CSRFProtection::regenerarToken();
    }

    /** Devuelve los datos del paso 1 si siguen vigentes; si no, null. */
    public static function paso1(): ?array
    {
        self::abrir(self::PRE);
        $cfg = require __DIR__ . '/../config/config.php';
        $p = $_SESSION['paso1'] ?? null;
        if (!$p || (time() - $p['hora']) > $cfg['vigencia_paso1']) {
            return null;
        }
        return $p;
    }

    /** Fase 2 superada: se destruye la sesión previa y se CREA OTRA sesión (APP_SESION). */
    public static function crearSesionApp(array $usuario): void
    {
        self::destruir(self::PRE);
        self::abrir(self::APP);
        session_regenerate_id(true);
        $_SESSION = [
            'autenticado'   => 'SI',
            'fase_2fa_ok'   => true,           // la solicitud del código fue exitosa
            'id'            => (int)$usuario['id'],
            'usuario'       => $usuario['Usuario'],
            'nombre'        => $usuario['Nombre'] . ' ' . $usuario['Apellido'],
            'correo'        => $usuario['Correo'],
            'inicio'        => time(),
        ];
        CSRFProtection::regenerarToken();
    }

    /** Protege páginas internas: exige la sesión definitiva con 2FA completado. */
    public static function exigirAcceso(): void
    {
        self::abrir(self::APP);
        if (($_SESSION['autenticado'] ?? '') !== 'SI' || empty($_SESSION['fase_2fa_ok'])) {
            header('Location: login.php');
            exit;
        }
    }
}
