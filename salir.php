<?php
require __DIR__ . '/includes/bootstrap.php';
SesionSegura::abrir(SesionSegura::APP);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRFProtection::validarToken($_POST['csrf_token'] ?? '')) {
    SesionSegura::destruir(SesionSegura::APP);
    ir('login.php?info=salida');
}
ir('panel.php');
