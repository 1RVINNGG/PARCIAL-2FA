<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';

SesionSegura::abrir(SesionSegura::PRE);
$repo = new UsuarioRepository(Conexion::obtener('app'));
$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'correo' => '', 'usuario' => '', 'sexo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::verificarFormulario();
    $registro = (new RegistroUsuario($repo, new BcryptHash($CFG['bcrypt_cost'])))->cargar($_POST);
    try {
        $id = $registro->guardar();
    } catch (PDOException $ex) {
        // Solo el error 1062 (clave UNIQUE duplicada: carrera entre dos registros) es "ya existe".
        // Cualquier otro error de BD (tabla/columna inexistente, permisos...) NO se disfraza.
        if ((int)($ex->errorInfo[1] ?? 0) !== 1062) {
            throw $ex;
        }
        $id = null;
        $errores['correo'] = 'El correo o usuario ya existe.';
    }
    if ($id) {
        // Registro creado: se pasa directo a generar y mostrar el QR 2FA
        SesionSegura::iniciarPaso1($repo->buscarPorId($id));
        ir('qr_setup.php');
    }
    $errores = $errores ?: $registro->getErrores();
    $datos = $registro->getDatos();
}

encabezado('Registro', false, 0, CSRFProtection::metaTag());
?>
<h2 style="text-align:center;margin-top:0">Crear cuenta</h2>
<?php if ($errores): ?><div class="alerta error">Revise los campos marcados.</div><?php endif; ?>
<form id="formRegistro" class="formulario" method="post" action="registro.php" novalidate autocomplete="off">
  <?= CSRFProtection::campoHidden() ?>
  <div class="fila">
    <div><label for="nombre">Nombre</label>
      <input type="text" id="nombre" name="nombre" required maxlength="60" value="<?= e($datos['nombre']) ?>" class="<?= isset($errores['nombre']) ? 'invalido' : '' ?>">
      <small class="ayuda error" id="ay_nombre"><?= e($errores['nombre'] ?? '') ?></small></div>
    <div><label for="apellido">Apellido</label>
      <input type="text" id="apellido" name="apellido" required maxlength="60" value="<?= e($datos['apellido']) ?>" class="<?= isset($errores['apellido']) ? 'invalido' : '' ?>">
      <small class="ayuda error" id="ay_apellido"><?= e($errores['apellido'] ?? '') ?></small></div>
  </div>
  <label for="correo">Correo electrónico</label>
  <input type="email" id="correo" name="correo" required maxlength="120" value="<?= e($datos['correo']) ?>" class="<?= isset($errores['correo']) ? 'invalido' : '' ?>">
  <small class="ayuda error" id="ay_correo"><?= e($errores['correo'] ?? '') ?></small>

  <div class="fila">
    <div><label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" required maxlength="30" value="<?= e($datos['usuario']) ?>" class="<?= isset($errores['usuario']) ? 'invalido' : '' ?>">
      <small class="ayuda error" id="ay_usuario"><?= e($errores['usuario'] ?? '') ?></small></div>
    <div><label for="sexo">Sexo</label>
      <select id="sexo" name="sexo" required class="<?= isset($errores['sexo']) ? 'invalido' : '' ?>">
        <option value="">Seleccione…</option>
        <option value="M" <?= $datos['sexo'] === 'M' ? 'selected' : '' ?>>Masculino</option>
        <option value="F" <?= $datos['sexo'] === 'F' ? 'selected' : '' ?>>Femenino</option>
      </select>
      <small class="ayuda error" id="ay_sexo"><?= e($errores['sexo'] ?? '') ?></small></div>
  </div>

  <label for="clave">Contraseña</label>
  <div class="campo"><input type="password" id="clave" name="clave" required autocomplete="new-password" class="<?= isset($errores['clave']) ? 'invalido' : '' ?>">
    <button type="button" class="ojo" data-para="clave" aria-label="Mostrar contraseña">Mostrar</button></div>
  <div class="fuerza" id="fuerza"><i></i></div>
  <small class="ayuda error" id="ay_clave"><?= e($errores['clave'] ?? '') ?></small>

  <label for="clave2">Confirmar contraseña</label>
  <div class="campo"><input type="password" id="clave2" name="clave2" required autocomplete="new-password" class="<?= isset($errores['clave2']) ? 'invalido' : '' ?>">
    <button type="button" class="ojo" data-para="clave2" aria-label="Mostrar contraseña">Mostrar</button></div>
  <small class="ayuda error" id="ay_clave2"><?= e($errores['clave2'] ?? '') ?></small>

  <button class="btn" type="submit">Registrarme y activar 2FA</button>
</form>
<p class="enlace">¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
<script src="js/registro.js"></script>
<?php pie();
