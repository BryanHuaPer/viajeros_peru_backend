<?php
// common.php - encabezados CORS, inicialización de sesión y conexión a la BD
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Iniciar sesión si no está iniciada (útil para APIs que usan $_SESSION)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta base al directorio backend/
$ruta_base = realpath(dirname(__FILE__) . '/../');

// Incluir configuración y conexión centralizada
require_once $ruta_base . '/configuracion.php';
require_once $ruta_base . '/base_datos/conexion.php';

// Ahora $GLOBALS['conexion'] debe estar disponible (establecido por conexion.php)

?>
