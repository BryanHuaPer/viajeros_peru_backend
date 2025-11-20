<?php
require_once __DIR__ . '/common.php';
require_once $ruta_base . '/../app/modelos/Usuario.php';
require_once $ruta_base . '/../app/helpers/validacion.php';
require_once $ruta_base . '/helpers/jwt.php';

// Log para debugging
error_log("=== PETICIÓN RECIBIDA EN AUTENTICACIÓN ===");
error_log("Método: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'No definido'));

try {
    // Determinar el método de obtención de datos
    $datos = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Leer contenido raw
        $raw = file_get_contents('php://input');
        error_log("Datos raw recibidos: " . $raw);

        if (!empty($raw)) {
            // Intentar decodificar JSON
            $maybeJson = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($maybeJson)) {
                $datos = $maybeJson;
                error_log("Entrada interpretada como JSON.");
            } else {
                // No es JSON válido: intentar parsear como form-urlencoded
                parse_str($raw, $parsed);
                if (!empty($parsed)) {
                    $datos = $parsed;
                    error_log("Entrada interpretada como form-urlencoded.");
                } else {
                    // Fallback a $_POST (por ejemplo si PHP ya lo llenó)
                    $datos = $_POST;
                    error_log("Entrada tomada desde \$_POST.");
                }
            }
        } else {
            // Sin raw body, usar $_POST
            $datos = $_POST;
            error_log("No se recibió raw body; usando \$_POST.");
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Para pruebas directas
        echo json_encode([
            'exito' => true,
            'mensaje' => 'API de autenticación funcionando',
            'instrucciones' => 'Usa POST para registro/login'
        ]);
        exit;
    }

    // Si no hay datos y es POST, error
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($datos)) {
        echo json_encode([
            'exito' => false, 
            'error' => 'No se recibieron datos',
            'debug' => 'Método: ' . $_SERVER['REQUEST_METHOD']
        ]);
        exit;
    }

    $accion = $datos['accion'] ?? '';

    // Si no hay acción específica
    if (empty($accion)) {
        echo json_encode([
            'exito' => false, 
            'error' => 'Acción no especificada',
            'acciones_validas' => ['registro', 'login']
        ]);
        exit;
    }

    switch ($accion) {
        case 'registro':
            manejarRegistro($datos);
            break;
        
        case 'login':
            manejarLogin($datos);
            break;
        
        case 'refresh': 
            manejarRefresh($datos);
            break;
        default:
            echo json_encode([
                'exito' => false, 
                'error' => 'Acción no válida: ' . $accion,
                'acciones_validas' => ['registro', 'login']
            ]);
            exit;
    }

} catch (Exception $e) {
    error_log("ERROR GENERAL en autenticación: " . $e->getMessage());
    echo json_encode([
        'exito' => false, 
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}

function manejarRegistro($datos) {
    error_log("📝 Iniciando registro...");
    
    // Validar datos requeridos
    $camposRequeridos = ['correo', 'contrasena', 'nombre', 'apellido', 'rol'];
    foreach ($camposRequeridos as $campo) {
        if (empty($datos[$campo])) {
            http_response_code(400);
            echo json_encode([
                'exito' => false,
                'error' => "El campo '$campo' es requerido"
            ]);
            return;
        }
    }
    
    try {
        $usuario = new Usuario($GLOBALS['conexion']);
        
        // Validar formato email
        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'exito' => false,
                'error' => 'Formato de correo inválido'
            ]);
            return;
        }
        
        // Validar contraseña (mínimo 6 caracteres)
        if (strlen($datos['contrasena']) < 6) {
            http_response_code(400);
            echo json_encode([
                'exito' => false,
                'error' => 'Contraseña debe tener al menos 6 caracteres'
            ]);
            return;
        }
        
        // Crear usuario
        $resultado = $usuario->crear([
            'correo' => $datos['correo'],
            'contrasena' => $datos['contrasena'],
            'nombre' => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'rol' => $datos['rol'] ?? 'viajero'
        ]);
        
        if (!$resultado['exito']) {
            http_response_code(409);
            echo json_encode($resultado);
            return;
        }
        
        // ✅ Usuario creado - Generar JWT
        $nuevoUsuario = $usuario->obtenerPorId($resultado['usuario_id']);
        
        $token = JWT::generarToken([
            'usuario_id' => $nuevoUsuario['id'],
            'correo' => $nuevoUsuario['correo'],
            'nombre' => $nuevoUsuario['nombre'],
            'rol' => $nuevoUsuario['rol']
        ]);
        
        error_log("✅ Registro exitoso para: " . $datos['correo']);
        
        // Respuesta exitosa con token
        http_response_code(201);
        echo json_encode([
            'exito' => true,
            'token' => $token,
            'usuario' => [
                'id' => (int)$nuevoUsuario['id'],
                'correo' => $nuevoUsuario['correo'],
                'nombre' => $nuevoUsuario['nombre'],
                'apellido' => $nuevoUsuario['apellido'],
                'rol' => $nuevoUsuario['rol'],
                'estado' => $nuevoUsuario['estado']
            ],
            'mensaje' => 'Usuario registrado exitosamente'
        ]);
        
    } catch (PDOException $e) {
        error_log("❌ Error de BD en registro: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'error' => 'Error al registrar usuario'
        ]);
    }
}

function manejarLogin($datos) {
    error_log("🔐 Iniciando login...");
    
    // Validar datos requeridos
    if (empty($datos['correo']) || empty($datos['contrasena'])) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'error' => 'Correo y contraseña son requeridos'
        ]);
        return;
    }
    
    try {
        $usuario = new Usuario($GLOBALS['conexion']);
        $datosUsuario = $usuario->obtenerPorCorreo($datos['correo']);
        
        // Usuario no existe
        if (!$datosUsuario) {
            http_response_code(401);
            echo json_encode([
                'exito' => false,
                'error' => 'Credenciales inválidas'
            ]);
            error_log("❌ Usuario no encontrado: " . $datos['correo']);
            return;
        }
        
        // Contraseña incorrecta
        if (!password_verify($datos['contrasena'], $datosUsuario['contrasena'])) {
            http_response_code(401);
            echo json_encode([
                'exito' => false,
                'error' => 'Credenciales inválidas'
            ]);
            error_log("❌ Contraseña incorrecta para: " . $datos['correo']);
            return;
        }
        
        // Usuario inactivo
        if ($datosUsuario['estado'] === 'inactivo' || $datosUsuario['estado'] === 'suspendido') {
            http_response_code(403);
            echo json_encode([
                'exito' => false,
                'error' => 'Cuenta inactiva o suspendida'
            ]);
            error_log("❌ Cuenta inactiva: " . $datos['correo']);
            return;
        }
        
        // ✅ CREDENCIALES VÁLIDAS - Generar JWT
        $token = JWT::generarToken([
            'usuario_id' => $datosUsuario['id'],
            'correo' => $datosUsuario['correo'],
            'nombre' => $datosUsuario['nombre'],
            'rol' => $datosUsuario['rol']
        ]);
        
        error_log("✅ Login exitoso para: " . $datos['correo']);
        
        // Respuesta exitosa con token
        http_response_code(200);
        echo json_encode([
            'exito' => true,
            'token' => $token,
            'usuario' => [
                'id' => (int)$datosUsuario['id'],
                'correo' => $datosUsuario['correo'],
                'nombre' => $datosUsuario['nombre'],
                'apellido' => $datosUsuario['apellido'],
                'rol' => $datosUsuario['rol'],
                'estado' => $datosUsuario['estado']
            ],
            'mensaje' => 'Login exitoso'
        ]);
        
    } catch (PDOException $e) {
        error_log("❌ Error de BD en login: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'error' => 'Error interno del servidor'
        ]);
    }
}
function manejarRefresh($datos) {
    error_log("🔄 Intentando refrescar token...");
    
    if (empty($datos['token'])) {
        http_response_code(400);
        echo json_encode([
            'exito' => false,
            'error' => 'Token no proporcionado'
        ]);
        return;
    }
    
    try {
        // Validar token anterior
        $payload = JWT::validarToken($datos['token']);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'exito' => false,
                'error' => 'Token inválido o expirado'
            ]);
            return;
        }
        
        // Generar nuevo token
        $nuevoToken = JWT::generarToken([
            'usuario_id' => $payload['usuario_id'],
            'correo' => $payload['correo'],
            'nombre' => $payload['nombre'],
            'rol' => $payload['rol']
        ]);
        
        error_log("✅ Token refrescado para usuario: " . $payload['usuario_id']);
        
        echo json_encode([
            'exito' => true,
            'token' => $nuevoToken,
            'mensaje' => 'Token refrescado'
        ]);
        
    } catch (Exception $e) {
        error_log("❌ Error refrescando token: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'exito' => false,
            'error' => 'Error refrescando token'
        ]);
    }
}