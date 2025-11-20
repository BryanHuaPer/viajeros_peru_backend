<?php
// Configuración unificada para Railway
class Configuracion {

    public static function getDBConfig() {
        return [
            'servidor' => getenv('MYSQLHOST') ?: 'localhost',
            'puerto' => getenv('MYSQLPORT') ?: '3306',
            'nombre' => getenv('MYSQLDATABASE') ?: 'viajeros_peru',
            'usuario' => getenv('MYSQLUSER') ?: 'root',
            'contrasena' => getenv('MYSQLPASSWORD') ?: ''
        ];
    }
    
    // Configuración de la aplicación
    const APP_NOMBRE = 'Viajeros Perú';
    const APP_VERSION = '1.0.0';
    
    // Configuración de seguridad
    const CLAVE_SECRETA = null; // Se configurará en Railway
    
    public static function getJWTSecret() {
        return getenv('JWT_SECRET') ?: 'viajeros_peru_clave_secreta_2024';
    }
    
    // Configuración de subida de archivos (ajustar para Railway)
    public static function getUploadPath() {
        return getenv('UPLOAD_PATH') ?: __DIR__ . '/uploads/';
    }
    
    public static function getUploadUrl() {
        return getenv('UPLOAD_URL') ?: '/uploads/';
    }
    
    const TAMANIO_MAXIMO = 5 * 1024 * 1024;
    const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/gif'];
}

// Manejo de errores - En producción mostrar menos errores
if (getenv('RAILWAY_ENVIRONMENT') === 'production') {
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Configuración de zona horaria
date_default_timezone_set('America/Lima');

// Iniciar sesión si es necesario
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

error_log("✅ Configuración Railway cargada");
?>