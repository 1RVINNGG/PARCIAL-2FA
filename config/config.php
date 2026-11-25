<?php
// Configuración central. NUNCA se usa el super usuario root: la app se conecta
// con usuarios de privilegios mínimos creados en sql/02_usuarios_privilegios_minimos.sql
return [
    'db' => [
        'host'    => getenv('DB_HOST') ?: 'localhost',
        'puerto'  => (int)(getenv('DB_PORT') ?: 3306),
        'nombre'  => 'login2fa',
        'charset' => 'utf8mb4',
    ],
    'perfiles' => [
        // Lee/inserta/actualiza solo lo necesario para registro y login
        'app'     => ['usuario' => 'app_2fa',    'clave' => 'App2FA#2026!'],
        // Solo lectura (sin columnas sensibles) para reportes
        'lectura' => ['usuario' => 'lector_2fa', 'clave' => 'Lector2FA#2026!'],
    ],
    'debug'             => true,  // true: muestra el error técnico de la BD en pantalla (ponerlo en false al entregar/producción)
    'app_nombre'        => 'MiSistema2FA',
    'bcrypt_cost'       => 12,
    'max_fallos_login'  => 5,    // intentos fallidos permitidos...
    'ventana_bloqueo'   => 10,   // ...dentro de estos minutos
    'max_intentos_2fa'  => 5,    // intentos del código TOTP por inicio de sesión
    'vigencia_paso1'    => 300,  // segundos que dura la sesión previa (usuario+clave ok)
];
