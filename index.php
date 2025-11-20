<?php
// index.php en RAÍZ
require_once 'configuracion.php';
require_once 'base_datos/conexion.php';

header('Content-Type: application/json');

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

error_log("🎯 PATH: " . $path);

// SIMPLE HEALTH CHECK
if ($path === '/' || $path === '/health') {
    echo json_encode(['status' => 'online', 'message' => '¡Funciona desde raíz!']);
    exit;
}

// TEST BD
if ($path === '/test') {
    try {
        $conexionBD = new ConexionBD();
        echo json_encode(['status' => 'success', 'message' => '✅ BD Conectada']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// CUALQUIER OTRA RUTA
echo json_encode([
    'status' => 'online',
    'path' => $path,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>