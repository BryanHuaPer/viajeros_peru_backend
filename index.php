<?php
// index.php - Punto de entrada principal
require_once 'configuracion.php';
require_once 'base_datos/conexion.php';

// Routing básico
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Configurar CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Enrutamiento simple
switch ($path) {
    case '/':
        echo json_encode(['message' => 'API Viajeros Perú Backend']);
        break;
    case '/api/test':
        require 'test_connection.php';
        break;
    default:
        // Para rutas de API, redirigir al archivo correspondiente
        if (str_starts_with($path, '/api/')) {
            $api_file = substr($path, 5) . '.php'; // quita "/api/"
            if (file_exists("api/$api_file")) {
                require "api/$api_file";
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint no encontrado']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Ruta no encontrada']);
        }
        break;
}
?>