<?php
// index.php - Punto de entrada principal
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

error_log("📥 Request recibido: " . $path);

switch ($path) {
    case '/':
        echo json_encode([
            'mensaje' => 'API Viajeros Perú Backend - ONLINE',
            'estado' => 'activo',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case '/test':
    case '/test.php':
        require 'test_connection.php';
        break;
        
    case '/health':
        echo json_encode(['status' => 'ok', 'server' => 'php-builtin']);
        break;
        
    default:
        // Routing para API
        if (str_starts_with($path, '/api/')) {
            $api_file = substr($path, 5) . '.php';
            if (file_exists("api/$api_file")) {
                require "api/$api_file";
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint API no encontrado: ' . $api_file]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Ruta no encontrada: ' . $path]);
        }
        break;
}
?>