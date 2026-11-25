<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';

// Si ya hay sesión definitiva (con 2FA completado) se va directo al panel
if (isset($_COOKIE[SesionSegura::APP])) {
    SesionSegura::abrir(SesionSegura::APP);
    if (($_SESSION['autenticado'] ?? '') === 'SI' && !empty($_SESSION['fase_2fa_ok'])) {
        ir('panel.php');
    }
}
SesionSegura::abrir(SesionSegura::PRE);

$aviso = '';
$error = '';
$mensajes = [
    'expirado'  => 'La verificación expiró. Inicie sesión nuevamente.',
    'bloqueo2fa'=> 'Demasiados códigos incorrectos. Inicie sesión nuevamente.',
    'salida'    => 'Sesión cerrada correctamente.',
];
if (($_GET['registro'] ?? '') === 'ok') {
    $aviso = '2FA activado. Ahora inicie sesión: se le pedirá el código de Google Authenticator.';
} elseif (isset($mensajes[$_GET['info'] ?? ''])) {
    $aviso = $mensajes[$_GET['info']];
}

$login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::verificarFormulario();
    $login = SanitizarEntrada::limpiarCadena($_POST['login'] ?? '');
    $clave = SanitizarEntrada::limpiarClave($_POST['clave'] ?? '');
    $repo  = new UsuarioRepository(Conexion::obtener('app'));
    $val   = new ValidacionLogin($repo, new BcryptHash($CFG['bcrypt_cost']), $login, $clave,
                                 ipCliente(), $CFG['max_fallos_login'], $CFG['ventana_bloqueo']);
    if ($val->autenticar()) {
        $u = $val->getUsuario();
        SesionSegura::iniciarPaso1($u);                       // sesión previa: aún SIN acceso
        ir((int)$u['confirmado_2fa'] === 1 ? 'verificar_2fa.php' : 'qr_setup.php');
    }
    $error = $val->getMotivo();
}

encabezado('Iniciar sesión', false, 1);
?>
<?php if ($aviso): ?><div class="alerta ok"><?= e($aviso) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta error"><?= e($error) ?></div><?php endif; ?>
<form class="formulario" method="post" action="login.php" autocomplete="off">
  <?= CSRFProtection::campoHidden() ?>
  <label for="login">Usuario o correo</label>
  <input type="text" id="login" name="login" required autofocus value="<?= e($login) ?>">
  <label for="clave">Contraseña</label>
  <div class="campo"><input type="password" id="clave" name="clave" required autocomplete="current-password">
    <button type="button" class="ojo" id="verClave" aria-label="Mostrar contraseña">Mostrar</button></div>
  <small class="ayuda error aviso-caps" id="caps">Bloq Mayús está activado</small>
  <button class="btn" type="submit">Continuar</button>
</form>
<p class="enlace">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
<p class="ayuda" style="text-align:center">Protegido con verificación en dos pasos (TOTP)</p>
<script>
const c = document.getElementById('clave');
const bv = document.getElementById('verClave');
bv.onclick = () => { const oculto = c.type === 'password'; c.type = oculto ? 'text' : 'password'; bv.textContent = oculto ? 'Ocultar' : 'Mostrar'; };
c.addEventListener('keyup', e => document.getElementById('caps').style.display = e.getModifierState('CapsLock') ? 'block' : 'none');
</script>
<?php pie();
