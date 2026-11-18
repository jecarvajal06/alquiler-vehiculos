<?php
// datos de la base de datos (cambiar aqui si hace falta)
$host = 'localhost';
$bd = 'alquiler_vehiculos_db';
$usuario = 'root';
$clave = '';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// responde con json y termina
function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

function error($mensaje, $codigo = 400) {
    responder(['error' => $mensaje], $codigo);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$bd;charset=utf8mb4", $usuario, $clave);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error('No se pudo conectar a la base de datos', 500);
}

$metodo = $_SERVER['REQUEST_METHOD'];
$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    $datos = [];
}
