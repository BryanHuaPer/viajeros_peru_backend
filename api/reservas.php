<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Reserva.php';

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

    $reservaModel = new Reserva($GLOBALS['conexion']);

    switch ($accion) {
        case 'crear':
            if (empty($datos['anuncio_id']) || empty($datos['viajero_id']) || empty($datos['fecha_inicio']) || empty($datos['fecha_fin'])) {
                echo json_encode(['exito' => false, 'error' => 'Datos incompletos para crear reserva']);
                return;
            }
            
            $resultado = $reservaModel->crear($datos);
            echo json_encode($resultado);
            break;
        
        case 'obtener_por_viajero':
            if (empty($datos['viajero_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de viajero no especificado']);
                return;
            }
            
            $reservas = $reservaModel->obtenerPorViajero($datos['viajero_id']);
            echo json_encode(['exito' => true, 'reservas' => $reservas]);
            break;
        
        case 'obtener_por_anfitrion':
            if (empty($datos['anfitrion_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de anfitrión no especificado']);
                return;
            }
            
            $reservas = $reservaModel->obtenerPorAnfitrion($datos['anfitrion_id']);
            echo json_encode(['exito' => true, 'reservas' => $reservas]);
            break;
        
        case 'actualizar_estado':
            if (empty($datos['id']) || empty($datos['estado'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de reserva o estado no especificado']);
                return;
            }
            
            $anfitrionId = $datos['anfitrion_id'] ?? null;
            $resultado = $reservaModel->actualizarEstado($datos['id'], $datos['estado'], $anfitrionId);
            echo json_encode($resultado);
            break;
        
        case 'cancelar':
            if (empty($datos['id']) || empty($datos['viajero_id'])) {
                echo json_encode(['exito' => false, 'error' => 'ID de reserva o viajero no especificado']);
                return;
            }
            
            $resultado = $reservaModel->cancelar($datos['id'], $datos['viajero_id']);
            echo json_encode($resultado);
            break;
        case 'estadisticas_viajero':
            require_once __DIR__ . '/common.php';
            
            $viajero_id = $_GET['viajero_id'] ?? 0;
            
            try {
                // 1. Solicitudes enviadas
                $sql_enviadas = "SELECT COUNT(*) as total FROM reservas WHERE viajero_id = ?";
                $stmt = $conexion->prepare($sql_enviadas);
                $stmt->execute([$viajero_id]);
                $solicitudes_enviadas = $stmt->fetchColumn();
                
                // 2. Solicitudes aceptadas
                $sql_aceptadas = "SELECT COUNT(*) as total FROM reservas WHERE viajero_id = ? AND estado = 'aceptada'";
                $stmt = $conexion->prepare($sql_aceptadas);
                $stmt->execute([$viajero_id]);
                $solicitudes_aceptadas = $stmt->fetchColumn();
                
                // 3. Reseñas recibidas (que anfitriones hicieron sobre el viajero)
                $sql_resenas = "SELECT COUNT(*) as total FROM resenas WHERE destinatario_id = ?";
                $stmt = $conexion->prepare($sql_resenas);
                $stmt->execute([$viajero_id]);
                $reseñas_recibidas = $stmt->fetchColumn(); // FALTABA ESTA LÍNEA

                // 4. Calificación promedio del viajero
                $sql_calificacion_viajero = "SELECT ROUND(AVG(puntuacion), 1) as promedio 
                                            FROM resenas 
                                            WHERE destinatario_id = ?";
                $stmt = $conexion->prepare($sql_calificacion_viajero);
                $stmt->execute([$viajero_id]);
                $calificacion_promedio = $stmt->fetchColumn();
                
                $estadisticas = [
                    'solicitudes_enviadas' => (int)$solicitudes_enviadas,
                    'solicitudes_aceptadas' => (int)$solicitudes_aceptadas,
                    'reseñas_recibidas' => (int)$reseñas_recibidas,
                    'calificacion_promedio' => $calificacion_promedio ? (float)$calificacion_promedio : 0.0,
                ];
                                
                echo json_encode(['exito' => true, 'estadisticas' => $estadisticas]);
                
            } catch (PDOException $e) {
                error_log("Error en estadísticas viajero: " . $e->getMessage());
                echo json_encode(['exito' => false, 'error' => 'Error al cargar estadísticas']);
            }
            break;
        default:
            echo json_encode([
                'exito' => false, 
                'error' => 'Acción no válida: ' . $accion,
                'acciones_validas' => ['crear', 'obtener_por_viajero', 'obtener_por_anfitrion', 'actualizar_estado', 'cancelar']
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en reservas.php: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}
?>