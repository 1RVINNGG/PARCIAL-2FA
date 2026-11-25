<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';
SesionSegura::exigirAcceso();

// El listado se lee con el usuario de SOLO LECTURA (lector_2fa), que no ve HashMagic ni secret_2fa
$lectura   = new UsuarioRepository(Conexion::obtener('lectura'));
$usuarios  = $lectura->ultimosUsuarios(10);
$intentos  = $lectura->ultimosIntentos(10);

encabezado('Panel', true);
?>
<section class="panel bienvenida">
  <h2>¡Bienvenido, <?= e($_SESSION['nombre']) ?>!</h2>
  <p>Acceso concedido: usuario y contraseña <strong>+ código 2FA</strong> verificados.</p>
  <p>Usuario: <strong><?= e($_SESSION['usuario']) ?></strong> · <?= e($_SESSION['correo']) ?></p>
</section>

<section class="panel">
  <h2>Sesiones</h2>
  <p><span class="insignia ok">Fase de código 2FA exitosa</span></p>
  <ul>
    <li>Sesión previa <code>PREAUTH_2FA</code> (usuario + contraseña): <strong>destruida</strong>.</li>
    <li>Sesión nueva <code>APP_SESION</code>: activa, id <code><?= e(substr(session_id(), 0, 8)) ?>…</code>,
        iniciada <?= e(date('d/m/Y H:i:s', $_SESSION['inicio'])) ?>.</li>
  </ul>
</section>

<section class="panel">
  <h2>Tabla <code>usuarios</code> (últimos registros)</h2>
  <div class="tabla-wrap"><table>
    <tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Correo</th><th>Usuario</th><th>Sexo</th><th>2FA</th><th>Registro</th></tr>
    <?php foreach ($usuarios as $f): ?>
    <tr><td><?= (int)$f['id'] ?></td><td><?= e($f['Nombre']) ?></td><td><?= e($f['Apellido']) ?></td>
      <td><?= e($f['Correo']) ?></td><td><?= e($f['Usuario']) ?></td><td><?= e($f['Sexo']) ?></td>
      <td><span class="insignia <?= $f['confirmado_2fa'] ? 'ok' : 'no' ?>"><?= $f['confirmado_2fa'] ? 'Activo' : 'Pendiente' ?></span></td>
      <td><?= e($f['fecha_registro']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>

<section class="panel">
  <h2>Tabla <code>intentos_login</code> (bitácora)</h2>
  <div class="tabla-wrap"><table>
    <tr><th>ID</th><th>Usuario</th><th>IP</th><th>Etapa</th><th>Resultado</th><th>Fecha</th></tr>
    <?php foreach ($intentos as $f): ?>
    <tr><td><?= (int)$f['id'] ?></td><td><?= e($f['Usuario']) ?></td><td><?= e($f['ipRemoto']) ?></td>
      <td><?= e($f['etapa']) ?></td>
      <td><span class="insignia <?= $f['exitoso'] ? 'ok' : 'no' ?>"><?= $f['exitoso'] ? 'Exitoso' : 'Fallido' ?></span></td>
      <td><?= e($f['fecha']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
</section>
<?php pie(true);
