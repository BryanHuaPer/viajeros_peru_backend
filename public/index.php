<?php
// public/index.php
require_once __DIR__ . '/../configuracion.php';
require_once __DIR__ . '/../base_datos/conexion.php';

header('Content-Type: application/json');

// Configurar CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

error_log("🎯 Request recibido: " . $path);

// SIMPLE - Solo probar conexión primero
if ($path === '/test') {
    require __DIR__ . '/../test_connection.php';
    exit;
}

// HEALTH CHECK mínimo
if ($path === '/health' || $path === '/') {
    echo json_encode([
        'status' => 'online', 
        'message' => 'API Viajeros Perú',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Routing API
if (str_starts_with($path, '/api/')) {
    $api_file = substr($path, 5) . '.php'; // quita "/api/"
    $api_path = __DIR__ . '/../api/' . $api_file;
    
    error_log("🔍 Buscando archivo API: " . $api_path);
    
    if (file_exists($api_path)) {
        require $api_path;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint no encontrado', 'file' => $api_file]);
    }
    exit;
}

// 404 para cualquier otra ruta
http_response_code(404);
echo json_encode(['error' => 'Ruta no encontrada', 'path' => $path]);
?>