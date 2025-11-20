<?php
require_once '../configuracion.php';

class ConexionBD {
    private $conexion;

    public function __construct() {
        try {
            $config = Configuracion::getDBConfig();
            
            $dsn = "mysql:host=" . $config['servidor'] .
                   ";port=" . $config['puerto'] .
                   ";dbname=" . $config['nombre'] .
                   ";charset=utf8";

            $this->conexion = new PDO(
                $dsn,
                $config['usuario'],
                $config['contrasena'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            error_log("✅ Conexión a BD Railway exitosa");

        } catch (PDOException $e) {
            error_log("❌ Error de conexión a BD Railway: " . $e->getMessage());
            
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode([
                'exito' => false,
                'error' => 'Error de conexión a la base de datos',
                'debug' => 'Verifica las variables de entorno en Railway'
            ]);
            exit;
        }
    }

    // Mantener todos tus métodos existentes...
    public function obtenerConexion() {
        return $this->conexion;
    }

    public function ejecutar($sql, $parametros = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute($parametros);
            return $stmt;
        } catch (PDOException $e) {
            error_log("❌ Error en consulta SQL: " . $e->getMessage());
            error_log("SQL: " . $sql);
            return false;
        }
    }

    public function obtenerUno($sql, $parametros = []) {
        $stmt = $this->ejecutar($sql, $parametros);
        return $stmt ? $stmt->fetch() : false;
    }

    public function obtenerTodos($sql, $parametros = []) {
        $stmt = $this->ejecutar($sql, $parametros);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function obtenerUltimoId() {
        return $this->conexion->lastInsertId();
    }
}

// Inicializar conexión global
try {
    $conexionBD = new ConexionBD();
    $conexion = $conexionBD->obtenerConexion();
    $GLOBALS['conexion'] = $conexion;
} catch (Exception $e) {
    error_log("Error inicializando conexión Railway: " . $e->getMessage());
    $conexion = null;
}
?>