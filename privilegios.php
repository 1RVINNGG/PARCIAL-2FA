<?php
// Muestra en pantalla los privilegios de los usuarios MySQL usados por la app.
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';
SesionSegura::exigirAcceso();

$perfiles = [];
foreach (['app', 'lectura'] as $perfil) {
    $pdo = Conexion::obtener($perfil);
    $quien = $pdo->query('SELECT CURRENT_USER()')->fetchColumn();
    $grants = $pdo->query('SHOW GRANTS FOR CURRENT_USER()')->fetchAll(PDO::FETCH_COLUMN);
    // MariaDB incluye el hash de la clave del usuario en SHOW GRANTS: se oculta
    $grants = preg_replace("/IDENTIFIED BY PASSWORD '[^']*'/", "IDENTIFIED BY PASSWORD '***'", $grants);
    $perfiles[] = ['perfil' => $perfil, 'usuario' => $quien, 'grants' => $grants];
}
encabezado('Privilegios', true);
?>
<section class="panel">
  <h2>Usuarios de BD con privilegios mínimos</h2>
  <p>La aplicación <strong>no usa el super usuario root</strong>. Comando usado para ver los privilegios concedidos:</p>
  <pre>SHOW GRANTS FOR 'app_2fa'@'localhost';
SHOW GRANTS FOR 'lector_2fa'@'localhost';
-- o, estando conectado como el usuario:
SHOW GRANTS FOR CURRENT_USER();</pre>
</section>
<?php foreach ($perfiles as $p): ?>
<section class="panel">
  <h2>Usuario <code><?= e($p['usuario']) ?></code> <small class="sub">(perfil «<?= e($p['perfil']) ?>»)</small></h2>
  <pre><?php foreach ($p['grants'] as $g) { echo e($g) . "\n"; } ?></pre>
</section>
<?php endforeach; ?>
<section class="panel">
  <h2>Script de creación</h2>
  <pre>CREATE USER 'app_2fa'@'localhost' IDENTIFIED BY '********';
GRANT SELECT, INSERT, UPDATE ON login2fa.usuarios       TO 'app_2fa'@'localhost';
GRANT SELECT, INSERT         ON login2fa.intentos_login TO 'app_2fa'@'localhost';

CREATE USER 'lector_2fa'@'localhost' IDENTIFIED BY '********';
GRANT SELECT (id, Nombre, Apellido, Correo, Usuario, Sexo, fecha_registro, confirmado_2fa)
      ON login2fa.usuarios TO 'lector_2fa'@'localhost';
GRANT SELECT ON login2fa.intentos_login TO 'lector_2fa'@'localhost';</pre>
</section>
<?php pie(true);
