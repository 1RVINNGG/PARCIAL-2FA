<?php
// Registro 2FA: genera el secreto, muestra el código QR y confirma el primer código.
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';

$p = SesionSegura::paso1();
if (!$p) { ir('login.php?info=expirado'); }

$repo = new UsuarioRepository(Conexion::obtener('app'));
$u = $repo->buscarPorId($p['id']);
if (!$u) { ir('login.php'); }
if ((int)$u['confirmado_2fa'] === 1) { ir('verificar_2fa.php'); }

$a2fa = new Autenticador2FA($CFG['app_nombre']);
$secretoBD = SanitizarEntrada::limpiarSecreto($u['secret_2fa'] ?? '');
if ($secretoBD === '') {                       // se genera UNA vez y se guarda por usuario
    $secreto = $a2fa->generarSecreto();
    $repo->guardarSecreto((int)$u['id'], $secreto);
} else {
    $secreto = $secretoBD;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::verificarFormulario();
    $codigo = SanitizarEntrada::limpiarCodigo($_POST['codigo'] ?? '');
    $ok = $a2fa->verificar($secreto, $codigo);
    $repo->registrarIntento($u['Usuario'], ipCliente(), 'REGISTRO_2FA', $ok);
    if ($ok) {
        $repo->confirmar2fa((int)$u['id']);
        SesionSegura::destruir(SesionSegura::PRE);     // debe iniciar sesión de verdad
        ir('login.php?registro=ok');
    }
    $_SESSION['paso1']['fallos_2fa']++;
    if ($_SESSION['paso1']['fallos_2fa'] >= $CFG['max_intentos_2fa']) {
        SesionSegura::destruir(SesionSegura::PRE);
        ir('login.php?info=bloqueo2fa');
    }
    $error = 'Código incorrecto. Verifique la hora de su teléfono e intente de nuevo.';
}

$urlQr = $a2fa->urlQr($u['Correo'], $secreto);
encabezado('Activar 2FA', false, 2);
?>
<h2 style="text-align:center;margin-top:0">Vincula tu cuenta</h2>
<ol class="lista-pasos">
  <li>Abre <strong>Google Authenticator</strong> (o Authy) en tu teléfono.</li>
  <li>Toca <strong>+</strong> y luego <em>Escanear código QR</em>.</li>
  <li>Escribe abajo el código de 6 dígitos que aparece.</li>
</ol>
<div class="qr"><img src="<?= e($urlQr) ?>" width="220" height="220" alt="Escanea este QR con Google Authenticator"></div>
<p class="ayuda" style="text-align:center">¿No puedes escanear? Ingresa esta clave manualmente:</p>
<div class="secreto"><?= e($secreto) ?></div>
<?php if ($error): ?><div class="alerta error"><?= e($error) ?></div><?php endif; ?>
<form class="formulario" method="post" action="qr_setup.php" autocomplete="off">
  <?= CSRFProtection::campoHidden() ?>
  <label for="codigo">Código de verificación</label>
  <input type="text" id="codigo" name="codigo" class="codigo" inputmode="numeric" maxlength="6" pattern="\d{6}" required autofocus placeholder="000000">
  <button class="btn" type="submit">Activar y continuar</button>
</form>
<p class="enlace"><a href="login.php">Cancelar</a></p>
<?php pie();
