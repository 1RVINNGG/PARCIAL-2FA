<?php
// Endpoint AJAX: ¿ya existe este correo / usuario? (validación en el frontend)
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}
SesionSegura::abrir(SesionSegura::PRE);
CSRFProtection::verificarAjax();   // lee X-CSRF-Token (header) o csrf_token (POST)
$campo = $_POST['campo'] ?? '';
$repo  = new UsuarioRepository(Conexion::obtener('app'));
if ($campo === 'correo') {
    $existe = $repo->existeCorreo(SanitizarEntrada::limpiarEmail($_POST['valor'] ?? ''));
} elseif ($campo === 'usuario') {
    $existe = $repo->existeUsuario(SanitizarEntrada::limpiarUsuario($_POST['valor'] ?? ''));
} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Campo no válido']));
}
echo json_encode(['existe' => $existe]);
