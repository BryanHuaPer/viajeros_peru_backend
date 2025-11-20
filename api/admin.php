<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Usuario.php';
require_once $ruta_base . '/../app/modelos/Anuncio.php';

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

    $usuarioModel = new Usuario($GLOBALS['conexion']);
    $anuncioModel = new Anuncio($GLOBALS['conexion']);

    switch ($accion) {
        case 'suspender_usuario':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            $resultado = $usuarioModel->actualizarEstado($datos['usuario_id'], 'inactivo');
            echo json_encode($resultado);
            break;
        
        case 'activar_usuario':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            $resultado = $usuarioModel->actualizarEstado($datos['usuario_id'], 'activo');
            echo json_encode($resultado);
            break;
        
        case 'eliminar_usuario':
            if (empty($datos['usuario_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
                return;
            }
            
            // Primero eliminar anuncios del usuario si es anfitrión
            $sqlAnuncios = "DELETE FROM anuncios WHERE anfitrion_id = ?";
            $stmtAnuncios = $GLOBALS['conexion']->prepare($sqlAnuncios);
            $stmtAnuncios->execute([$datos['usuario_id']]);
            
            // Luego eliminar el usuario
            $sql = "DELETE FROM usuarios WHERE id = ? AND rol != 'administrador'";
            $stmt = $GLOBALS['conexion']->prepare($sql);
            $stmt->execute([$datos['usuario_id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['exito' => true, 'mensaje' => 'Usuario eliminado correctamente']);
            } else {
                echo json_encode(['exito' => false, 'error' => 'No se pudo eliminar el usuario']);
            }
            break;
        
        case 'eliminar_anuncio':
            if (empty($datos['anuncio_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de anuncio no especificado']);
                return;
            }
            
            // Como admin, podemos eliminar cualquier anuncio
            $sql = "DELETE FROM anuncios WHERE id = ?";
            $stmt = $GLOBALS['conexion']->prepare($sql);
            $stmt->execute([$datos['anuncio_id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['exito' => true, 'mensaje' => 'Anuncio eliminado correctamente']);
            } else {
                echo json_encode(['exito' => false, 'error' => 'No se pudo eliminar el anuncio']);
            }
            break;
        
        default:
            echo json_encode([
                'exito' => false, 
                'error' => 'Acción no válida: ' . $accion,
                'acciones_validas' => ['suspender_usuario', 'activar_usuario', 'eliminar_usuario', 'eliminar_anuncio']
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en admin.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>