<?php
// public/index.php - VERSIÓN SUPER SIMPLE
header('Content-Type: application/json');

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

error_log("🎯 PATH: " . $path);

// SIMPLE HEALTH CHECK
if ($path === '/' || $path === '/health') {
    echo json_encode(['status' => 'online', 'message' => '¡Funciona!']);
    exit;
}

// TEST CONEXIÓN BD
if ($path === '/test') {
    try {
        require_once __DIR__ . '/../configuracion.php';
        require_once __DIR__ . '/../base_datos/conexion.php';
        
        $conexionBD = new ConexionBD();
        $conexion = $conexionBD->obtenerConexion();
        
        echo json_encode([
            'status' => 'success', 
            'message' => '✅ Conexión BD exitosa',
            'database' => 'Conectado a Railway MySQL'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => '❌ Error BD: ' . $e->getMessage()
        ]);
    }
    exit;
}

// CUALQUIER OTRA RUTA
echo json_encode([
    'status' => 'online',
    'message' => 'API Viajeros Perú',
    'path_requested' => $path,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>