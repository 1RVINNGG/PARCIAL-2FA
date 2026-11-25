<?php
declare(strict_types=1);

$raiz = dirname(__DIR__);
require_once $raiz . '/vendor/autoload.php';
spl_autoload_register(function (string $clase) use ($raiz): void {
    $f = "$raiz/clases/$clase.php";
    if (is_file($f)) {
        require_once $f;
    }
});
$CFG = require $raiz . '/config/config.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function e(?string $t): string { return SanitizarEntrada::escapar($t); }
function ipCliente(): string   { return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45); }
function ir(string $url): void { header('Location: ' . $url); exit; }

/** Errores de base de datos: mensaje claro en pantalla (y detalle técnico si debug=true) en vez de un 500 en blanco. */
set_exception_handler(function (Throwable $ex) use ($CFG): void {
    error_log('[Lab2FA] ' . get_class($ex) . ': ' . $ex->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $esBD  = $ex instanceof PDOException;
    $titulo = $esBD ? 'No se pudo usar la base de datos' : 'Error inesperado';
    $ayuda = $esBD
        ? 'Verifique que MySQL esté iniciado y que ejecutó sql/01_base_datos_y_tablas.sql y sql/02_usuarios_privilegios_minimos.sql (en ese orden).'
        : 'Intente de nuevo.';
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Error</title>'
       . '<body style="font-family:sans-serif;max-width:640px;margin:3rem auto;padding:0 1rem">'
       . '<h2>' . htmlspecialchars($titulo) . '</h2><p>' . htmlspecialchars($ayuda) . '</p>';
    if (!empty($CFG['debug'])) {
        echo '<pre style="background:#fee;padding:1rem;white-space:pre-wrap">'
           . htmlspecialchars($ex->getMessage()) . '</pre>';
    }
    echo '<p><a href="login.php">Volver</a></p></body>';
    exit;
});
