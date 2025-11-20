<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Usuario.php';

try {
    $datos = $_GET;
    $accion = $datos['accion'] ?? '';

    $usuarioModel = new Usuario($GLOBALS['conexion']);

    if ($accion === 'obtener_todos') {
        $usuarios = $usuarioModel->obtenerTodos();
        echo json_encode(['exito' => true, 'usuarios' => $usuarios]);
    } else {
        echo json_encode(['exito' => false, 'error' => 'Acción no válida']);
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en usuarios.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor'
    ]);
}
?>