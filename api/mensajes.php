<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Mensaje.php';

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

    $mensajeModel = new Mensaje($GLOBALS['conexion']);

    switch ($accion) {
        case 'enviar':
            if (empty($datos['remitente_id']) || empty($datos['destinatario_id']) || empty($datos['contenido'])) {
                echo json_encode(['exito' => false, 'error' => 'Datos incompletos para enviar mensaje']);
                return;
            }
            
            $resultado = $mensajeModel->enviar($datos);
            echo json_encode($resultado);
            break;
        
        case 'obtener_conversacion':
            if (empty($datos['usuario1']) || empty($datos['usuario2'])) {
                echo json_encode(['exito' => false, 'error' => 'IDs de usuarios no especificados']);
                return;
            }
            
            $anuncioId = $datos['anuncio_id'] ?? null;
            $conversacion = $mensajeModel->obtenerConversacion($datos['usuario1'], $datos['usuario2'], $anuncioId);
            echo json_encode(['exito' => true, 'mensajes' => $conversacion]);
            break;
        
        case 'obtener_chats':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            $chats = $mensajeModel->obtenerChats($datos['usuario_id']);
            echo json_encode(['exito' => true, 'chats' => $chats]);
            break;
        
        case 'marcar_leidos':
            if (empty($datos['remitente_id']) || empty($datos['destinatario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'IDs de usuarios no especificados']);
                return;
            }
            
            $resultado = $mensajeModel->marcarLeidos($datos['remitente_id'], $datos['destinatario_id']);
            echo json_encode($resultado);
            break;
        
        case 'obtener_no_leidos':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            $totalNoLeidos = $mensajeModel->obtenerTotalNoLeidos($datos['usuario_id']);
            echo json_encode(['exito' => true, 'total_no_leidos' => $totalNoLeidos]);
            break;
        default:
            echo json_encode([
                'exito' => false, 
                'error' => 'Acción no válida: ' . $accion,
                'acciones_validas' => ['enviar', 'obtener_conversacion', 'obtener_chats', 'marcar_leidos']
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en mensajes.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>