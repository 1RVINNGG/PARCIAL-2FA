<?php
// Interfaz para GENERAR y VALIDAR hashes (POO: HashInterface + ServicioHash)
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/plantilla.php';
SesionSegura::exigirAcceso();

$res = ['generado' => null, 'info' => null, 'validacion' => null, 'algoritmo' => 'bcrypt'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::verificarFormulario();
    $res['algoritmo'] = ($_POST['algoritmo'] ?? '') === 'argon2id' ? 'argon2id' : 'bcrypt';
    $servicio = ServicioHash::crear($res['algoritmo'], $CFG['bcrypt_cost']);
    $texto = SanitizarEntrada::limpiarClave($_POST['texto'] ?? '');

    if (($_POST['accion'] ?? '') === 'generar' && $texto !== '') {
        $res['generado'] = $servicio->generar($texto);
        $res['info'] = $servicio->describir($res['generado']);
    } elseif (($_POST['accion'] ?? '') === 'validar' && $texto !== '') {
        $hash = SanitizarEntrada::limpiarCadena($_POST['hash'] ?? '');
        $res['validacion'] = $servicio->validar($texto, $hash);
    }
}
encabezado('Hash', true);
?>
<section class="panel">
  <h2>Generar y validar hash</h2>
  <p class="sub">Implementado con la interfaz <code>HashInterface</code> (<code>BcryptHash</code>, <code>Argon2Hash</code>) y <code>ServicioHash</code>.</p>
</section>
<div class="dos-col">
<section class="panel">
  <h2>Generar</h2>
  <form class="formulario" method="post" autocomplete="off">
    <?= CSRFProtection::campoHidden() ?><input type="hidden" name="accion" value="generar">
    <label for="t1">Texto / contraseña</label><input type="text" id="t1" name="texto" required>
    <label for="alg">Algoritmo</label>
    <select id="alg" name="algoritmo">
      <option value="bcrypt">bcrypt</option>
      <?php if (Argon2Hash::disponible()): ?><option value="argon2id">argon2id</option><?php endif; ?>
    </select>
    <button class="btn" type="submit">Generar hash</button>
  </form>
  <?php if ($res['generado']): ?>
    <h2>Resultado (<?= e($res['info']['algoritmo']) ?>)</h2>
    <div class="hash-salida"><?= e($res['generado']) ?></div>
    <p class="ayuda">Longitud: <?= strlen($res['generado']) ?> · Opciones: <?= e(json_encode($res['info']['info']['options'])) ?></p>
  <?php endif; ?>
</section>
<section class="panel">
  <h2>Validar</h2>
  <form class="formulario" method="post" autocomplete="off">
    <?= CSRFProtection::campoHidden() ?><input type="hidden" name="accion" value="validar">
    <label for="t2">Texto / contraseña</label><input type="text" id="t2" name="texto" required>
    <label for="h2">Hash</label><input type="text" id="h2" name="hash" required>
    <input type="hidden" name="algoritmo" value="bcrypt">
    <button class="btn sec" type="submit">Validar hash</button>
  </form>
  <?php if ($res['validacion'] !== null): ?>
    <div class="alerta <?= $res['validacion'] ? 'ok' : 'error' ?>">
      <?= $res['validacion'] ? 'El texto COINCIDE con el hash.' : 'El texto NO coincide con el hash.' ?></div>
  <?php endif; ?>
</section>
</div>
<?php pie(true);
