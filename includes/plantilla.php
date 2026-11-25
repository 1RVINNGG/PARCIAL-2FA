<?php
declare(strict_types=1);

/** $paso: 0 = sin indicador, 1 = credenciales, 2 = código 2FA */
function encabezado(string $titulo, bool $interno = false, int $paso = 0, string $extraHead = ''): void
{
    global $CFG; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · <?= e($CFG['app_nombre']) ?></title>
<link rel="stylesheet" href="css/estilo.css">
<?= $extraHead ?>
</head>
<body class="<?= $interno ? 'interno' : 'publico' ?>">
<?php if ($interno): ?>
<header class="topbar">
  <div class="marca"><?= e($CFG['app_nombre']) ?></div>
  <nav>
    <a href="panel.php">Panel</a>
    <a href="hash_tool.php">Hash</a>
    <a href="privilegios.php">Privilegios BD</a>
    <form action="salir.php" method="post" class="inline">
      <?= CSRFProtection::campoHidden() ?><button class="btn-link" type="submit">Salir</button>
    </form>
  </nav>
</header>
<main class="contenedor-ancho">
<?php else: ?>
<main class="centro">
  <section class="tarjeta">
    <div class="cabecera-tarjeta">
      <h1><?= e($CFG['app_nombre']) ?></h1>
      <p class="sub">Universidad Tecnológica de Panamá · Desarrollo Web</p>
    </div>
    <?php if ($paso): ?>
    <ol class="pasos">
      <li class="<?= $paso >= 1 ? 'activo' : '' ?>"><span>1</span> Credenciales</li>
      <li class="<?= $paso >= 2 ? 'activo' : '' ?>"><span>2</span> Código 2FA</li>
      <li><span>3</span> Acceso</li>
    </ol>
    <?php endif; ?>
<?php endif;
}

function pie(bool $interno = false): void
{
    if ($interno) { ?>
</main>
<footer class="pie">Lab 2FA · PHP + MySQL + Google Authenticator</footer>
<?php } else { ?>
  </section>
  <p class="pie">Lab 2FA · PHP + MySQL + Google Authenticator</p>
</main>
<?php } ?>
</body>
</html>
<?php
}
