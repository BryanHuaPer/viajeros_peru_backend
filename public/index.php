<?php
// public/index.php
require_once __DIR__ . '/../configuracion.php';

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

// Log para debugging
error_log("📥 Request: " . $path);

switch ($path) {
    case '/':
        echo json_encode([
            'mensaje' => 'API Viajeros Perú - FrankenPHP',
            'estado' => 'online',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case '/test':
        require __DIR__ . '/../test_connection.php';
        break;
        
    case '/health':
        echo json_encode(['status' => 'ok']);
        break;
        
    default:
        // Routing para API
        if (str_starts_with($path, '/api/')) {
            $api_file = substr($path, 5) . '.php';
            $api_path = __DIR__ . '/../api/' . $api_file;
            
            if (file_exists($api_path)) {
                require $api_path;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint no encontrado: ' . $api_file]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Ruta no encontrada: ' . $path]);
        }
        break;
}
?>