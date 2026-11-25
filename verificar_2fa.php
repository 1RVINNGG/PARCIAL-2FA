<?php
// Fase 2 del login: solicita el código TOTP antes de entrar al sistema.
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';

$p = SesionSegura::paso1();
if (!$p) { ir('login.php?info=expirado'); }

$repo = new UsuarioRepository(Conexion::obtener('app'));
$u = $repo->buscarPorId($p['id']);
if (!$u) { ir('login.php'); }
$secreto = SanitizarEntrada::limpiarSecreto($u['secret_2fa'] ?? '');
if ((int)$u['confirmado_2fa'] !== 1 || $secreto === '') { ir('qr_setup.php'); }

$a2fa = new Autenticador2FA($CFG['app_nombre']);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::verificarFormulario();
    $codigo = SanitizarEntrada::limpiarCodigo($_POST['codigo'] ?? '');
    $ok = $a2fa->verificar($secreto, $codigo);
    $repo->registrarIntento($u['Usuario'], ipCliente(), '2FA', $ok);
    if ($ok) {
        SesionSegura::crearSesionApp($u);              // se crea OTRA sesión (APP_SESION)
        ir('panel.php');
    }
    $_SESSION['paso1']['fallos_2fa']++;
    if ($_SESSION['paso1']['fallos_2fa'] >= $CFG['max_intentos_2fa']) {
        SesionSegura::destruir(SesionSegura::PRE);
        ir('login.php?info=bloqueo2fa');
    }
    $error = 'Código incorrecto. Acceso denegado.';
}

encabezado('Verificación 2FA', false, 2);
?>
<h2 style="text-align:center;margin-top:0">Verificación en dos pasos</h2>
<p class="sub" style="text-align:center">Hola <strong><?= e($u['Nombre']) ?></strong>. Ingresa el código de 6 dígitos de Google Authenticator.</p>
<?php if ($error): ?><div class="alerta error"><?= e($error) ?></div><?php endif; ?>
<form class="formulario" method="post" action="verificar_2fa.php" autocomplete="off">
  <?= CSRFProtection::campoHidden() ?>
  <label for="codigo">Código 2FA</label>
  <input type="text" id="codigo" name="codigo" class="codigo" inputmode="numeric" maxlength="6" pattern="\d{6}" required autofocus placeholder="000000">
  <button class="btn" type="submit">Verificar y entrar</button>
</form>
<p class="enlace"><a href="login.php">Cancelar</a></p>
<?php pie();
