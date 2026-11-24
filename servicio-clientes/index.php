<?php
// servicio de clientes (puerto 8002)
require __DIR__ . '/vendor/autoload.php';

use Clientes\BaseDatos;
use Clientes\Respuesta;

Respuesta::cabeceras();
$pdo = BaseDatos::conectar();

$metodo = $_SERVER['REQUEST_METHOD'];
$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    $datos = [];
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($metodo === 'GET') {
        if ($id) {
            $st = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
            $st->execute([$id]);
            $c = $st->fetch();
            if (!$c) Respuesta::error('Cliente no encontrado', 404);
            Respuesta::json($c);
        }
        Respuesta::json($pdo->query('SELECT * FROM clientes ORDER BY id')->fetchAll());
    }

    if ($metodo === 'POST' || $metodo === 'PUT') {
        $nombre = trim($datos['nombre'] ?? '');
        $telefono = $datos['telefono'] ?? null;
        $correo = $datos['correo'] ?? null;
        $licencia = $datos['numero_licencia'] ?? null;
        if ($nombre === '') Respuesta::error('El nombre es obligatorio');
        if ($metodo === 'POST') {
            $st = $pdo->prepare('INSERT INTO clientes (nombre, telefono, correo, numero_licencia) VALUES (?, ?, ?, ?)');
            $st->execute([$nombre, $telefono, $correo, $licencia]);
            Respuesta::json(['id' => (int)$pdo->lastInsertId(), 'mensaje' => 'Cliente creado'], 201);
        }
        if (!$id) Respuesta::error('Falta el id');
        $st = $pdo->prepare('SELECT id FROM clientes WHERE id = ?');
        $st->execute([$id]);
        if (!$st->fetch()) Respuesta::error('Cliente no encontrado', 404);
        $st = $pdo->prepare('UPDATE clientes SET nombre = ?, telefono = ?, correo = ?, numero_licencia = ? WHERE id = ?');
        $st->execute([$nombre, $telefono, $correo, $licencia, $id]);
        Respuesta::json(['mensaje' => 'Cliente actualizado']);
    }

    if ($metodo === 'DELETE') {
        if (!$id) Respuesta::error('Falta el id');
        $st = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE cliente_id = ?');
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) Respuesta::error('No se puede borrar, tiene reservas', 409);
        $st = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) Respuesta::error('Cliente no encontrado', 404);
        Respuesta::json(['mensaje' => 'Cliente borrado']);
    }

    Respuesta::error('Metodo no permitido', 405);
} catch (PDOException $e) {
    Respuesta::error('Error en la base de datos', 500);
}
