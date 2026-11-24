<?php
namespace Clientes;

use Dotenv\Dotenv;
use PDO;
use PDOException;

class BaseDatos
{
    // crea la conexion con los datos del .env
    public static function conectar()
    {
        Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $bd = $_ENV['DB_NAME'] ?? 'alquiler_vehiculos_db';
        $usuario = $_ENV['DB_USER'] ?? 'root';
        $clave = $_ENV['DB_PASS'] ?? '';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$bd;charset=utf8mb4", $usuario, $clave);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Respuesta::error('No se pudo conectar a la base de datos', 500);
        }
        return $pdo;
    }
}
