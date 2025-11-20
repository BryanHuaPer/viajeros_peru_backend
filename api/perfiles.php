<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Perfil.php';

// Log para debugging
error_log("=== PETICIÓN RECIBIDA EN PERFILES ===");
error_log("Método: " . $_SERVER['REQUEST_METHOD']);

try {
    // Determinar el método de obtención de datos
    $datos = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['foto'])) {
            // Es una subida de archivo
            $datos = $_POST;
            $datos['foto'] = $_FILES['foto'];
        } else {
            // Es JSON normal
            $datos_json = file_get_contents('php://input');
            if (!empty($datos_json)) {
                $datos = json_decode($datos_json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('JSON inválido: ' . json_last_error_msg());
                }
            }
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $datos = $_GET;
    }

    $accion = $datos['accion'] ?? '';

    // Si no hay acción específica y es GET, obtener perfil
    if (empty($accion) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $accion = 'obtener';
    }

    switch ($accion) {
        case 'obtener':
            manejarObtenerPerfil($datos);
            break;
        
        case 'guardar':
            manejarGuardarPerfil($datos);
            break;
        
        case 'subir_foto':
            manejarSubirFoto($datos);
            break;
            
        case 'ver_publico':
            manejarVerPerfilPublico($datos);
            break;
            
        case 'iniciar_verificacion':
            manejarIniciarVerificacion($datos);
            break;
        
        default:
            echo json_encode([
                'exito' => false, 
                'error' => 'Acción no válida: ' . $accion,
                'acciones_validas' => ['obtener', 'guardar', 'subir_foto']
            ]);
            exit;
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en perfiles: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}

function manejarObtenerPerfil($datos) {
    error_log("Obteniendo perfil para usuario: " . ($datos['usuario_id'] ?? 'No especificado'));
    
    if (empty($datos['usuario_id'])) {
        echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
        return;
    }
    
    $perfilModel = new Perfil($GLOBALS['conexion']);
    $perfil = $perfilModel->obtenerPorUsuarioId($datos['usuario_id']);
    
    if ($perfil) {
        echo json_encode([
            'exito' => true,
            'perfil' => $perfil
        ]);
    } else {
        echo json_encode([
            'exito' => false,
            'error' => 'Perfil no encontrado',
            'perfil' => null
        ]);
    }
}

function manejarVerPerfilPublico($datos) {
    error_log("Obteniendo perfil público para usuario: " . ($datos['usuario_id'] ?? 'No especificado'));
    
    if (empty($datos['usuario_id'])) {
        echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
        return;
    }
    
    $perfilModel = new Perfil($GLOBALS['conexion']);
    $perfilPublico = $perfilModel->obtenerPerfilPublico($datos['usuario_id']);
    
    if ($perfilPublico) {
        echo json_encode([
            'exito' => true,
            'perfil' => $perfilPublico
        ]);
    } else {
        echo json_encode([
            'exito' => false,
            'error' => 'Perfil no encontrado',
            'perfil' => null
        ]);
    }
}

function manejarIniciarVerificacion($datos) {
    error_log("Iniciando verificación para usuario: " . ($datos['usuario_id'] ?? 'No especificado'));
    
    if (empty($datos['usuario_id'])) {
        echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
        return;
    }
    
    try {
        // Validar archivos
        if (!isset($_FILES['documento_foto']) || !isset($_FILES['selfie_foto'])) {
            throw new Exception('Faltan archivos requeridos');
        }
        
        $perfilModel = new Perfil($GLOBALS['conexion']);
        
        // Guardar archivos de verificación
        $documentoPath = Configuracion::RUTA_SUBIDAS . 'verificacion/' . 'doc_' . $datos['usuario_id'] . '_' . time() . '.jpg';
        $selfiePath = Configuracion::RUTA_SUBIDAS . 'verificacion/' . 'selfie_' . $datos['usuario_id'] . '_' . time() . '.jpg';
        
        // Crear directorio si no existe
        if (!is_dir(dirname($documentoPath))) {
            mkdir(dirname($documentoPath), 0777, true);
        }
        
        // Mover archivos
        if (!move_uploaded_file($_FILES['documento_foto']['tmp_name'], $documentoPath) ||
            !move_uploaded_file($_FILES['selfie_foto']['tmp_name'], $selfiePath)) {
            throw new Exception('Error al guardar los archivos');
        }
        
        // Actualizar estado de verificación
        $resultado = $perfilModel->iniciarVerificacion($datos['usuario_id'], [
            'documento_path' => $documentoPath,
            'selfie_path' => $selfiePath,
            'nombre_completo' => $datos['nombre_completo'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'tipo_documento' => $datos['tipo_documento'],
            'numero_documento' => $datos['numero_documento']
        ]);
        
        if ($resultado['exito']) {
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Solicitud de verificación iniciada correctamente'
            ]);
        } else {
            throw new Exception($resultado['error']);
        }
        
    } catch (Exception $e) {
        error_log("Error en verificación: " . $e->getMessage());
        echo json_encode([
            'exito' => false,
            'error' => 'Error al procesar la verificación: ' . $e->getMessage()
        ]);
    }
}

function manejarGuardarPerfil($datos) {
    error_log("Guardando perfil: " . print_r($datos, true));
    
    if (empty($datos['usuario_id'])) {
        echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
        return;
    }
    
    $perfilModel = new Perfil($GLOBALS['conexion']);
    
    // Preparar datos COMPLETOS para guardar
    $datosPerfil = [
        'usuario_id' => $datos['usuario_id'],
        'biografia' => $datos['biografia'] ?? '',
        'telefono' => $datos['telefono'] ?? '',
        'pais' => $datos['pais'] ?? '',
        'ciudad' => $datos['ciudad'] ?? '',
        'ubicacion' => $datos['ubicacion'] ?? '',
        'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
        'idioma_preferido' => $datos['idioma_preferido'] ?? 'es',
        'zona_horaria' => $datos['zona_horaria'] ?? 'UTC',
        'disponibilidad' => $datos['disponibilidad'] ?? '',
        'habilidades' => json_encode($datos['habilidades'] ?? []),
        'idiomas' => json_encode($datos['idiomas'] ?? ['espanol']),
        'intereses' => json_encode($datos['intereses'] ?? []),
        'experiencias_previas' => $datos['experiencias_previas'] ?? '',
        'redes_sociales' => json_encode($datos['redes_sociales'] ?? [])
    ];
    
    $resultado = $perfilModel->guardar($datosPerfil);
    
    if ($resultado['exito']) {
        echo json_encode([
            'exito' => true,
            'mensaje' => 'Perfil guardado correctamente',
            'perfil' => $resultado['perfil']
        ]);
    } else {
        echo json_encode(['exito' => false, 'error' => $resultado['error']]);
    }
}

function manejarSubirFoto($datos) {
    error_log("Subiendo foto de perfil");
    
    if (empty($datos['usuario_id'])) {
        echo json_encode(['exito' => false, 'error' => 'ID de usuario no especificado']);
        return;
    }
    
    if (!isset($_FILES['foto'])) {
        echo json_encode(['exito' => false, 'error' => 'No se recibió ninguna foto']);
        return;
    }
    
    $foto = $_FILES['foto'];
    
    // Validar tipo de archivo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($foto['type'], $tiposPermitidos)) {
        echo json_encode(['exito' => false, 'error' => 'Tipo de archivo no permitido']);
        return;
    }
    
    // Validar tamaño (5MB máximo)
    if ($foto['size'] > 5 * 1024 * 1024) {
        echo json_encode(['exito' => false, 'error' => 'La imagen es demasiado grande (máximo 5MB)']);
        return;
    }
    
    // Crear directorio de uploads si no existe (filesystem)
    $directorioUploads = rtrim(Configuracion::RUTA_SUBIDAS, '/\\') . DIRECTORY_SEPARATOR . 'perfiles' . DIRECTORY_SEPARATOR;
    if (!is_dir($directorioUploads)) {
        mkdir($directorioUploads, 0777, true);
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
    $nombreArchivo = 'perfil_' . $datos['usuario_id'] . '_' . time() . '.' . $extension;
    $rutaCompleta = $directorioUploads . $nombreArchivo;
    
    // Mover archivo
    if (move_uploaded_file($foto['tmp_name'], $rutaCompleta)) {

    // Actualizar perfil con la nueva foto
    $perfilModel = new Perfil($GLOBALS['conexion']);

    // Construir URL pública usando la constante URL_SUBIDAS
    $urlFoto = rtrim(Configuracion::URL_SUBIDAS, '/\\') . '/perfiles/' . $nombreArchivo;

    $resultado = $perfilModel->actualizarFoto($datos['usuario_id'], $urlFoto);
        
        if ($resultado['exito']) {
            echo json_encode([
                'exito' => true,
                'mensaje' => 'Foto de perfil actualizada',
                'url_foto' => $urlFoto
            ]);
        } else {
            // Eliminar archivo si falló la actualización en BD
            unlink($rutaCompleta);
            echo json_encode(['exito' => false, 'error' => $resultado['error']]);
        }
    } else {
        echo json_encode(['exito' => false, 'error' => 'Error al subir el archivo']);
    }
}
?>