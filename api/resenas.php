<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Resena.php';

try {
    $datos = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos_json = file_get_contents('php://input');
        if (!empty($datos_json)) {
            $datos = json_decode($datos_json, true);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $datos = $_GET;
    }

    $accion = $datos['accion'] ?? '';

    $resenaModel = new Resena($GLOBALS['conexion']);

    switch ($accion) {
        case 'crear':
            if (empty($datos['reserva_id']) || empty($datos['autor_id']) || empty($datos['destinatario_id']) || empty($datos['puntuacion'])) {
                echo json_encode(['exito' => false, 'error' => 'Datos incompletos para crear reseña']);
                return;
            }
            
            $resultado = $resenaModel->crear($datos);
            echo json_encode($resultado);
            break;
        
        case 'obtener_por_usuario':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            $resenas = $resenaModel->obtenerPorUsuario($datos['usuario_id']);
            echo json_encode(['exito' => true, 'resenas' => $resenas]);
            break;
        
        case 'obtener_por_reserva':
            if (empty($datos['reserva_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de reserva no especificado']);
                return;
            }
            
            $resenas = $resenaModel->obtenerPorReserva($datos['reserva_id']);
            echo json_encode(['exito' => true, 'resenas' => $resenas]);
            break;
        
        case 'obtener_promedio':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            $promedio = $resenaModel->obtenerPromedioUsuario($datos['usuario_id']);
            echo json_encode(['exito' => true, 'promedio' => $promedio]);
            break;
        
        default:
            echo json_encode([
                'exito' => false, 
                'error' => 'Acción no válida: ' . $accion,
                'acciones_validas' => ['crear', 'obtener_por_usuario', 'obtener_por_reserva', 'obtener_promedio']
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en resenas.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>