<?php
// servicio de vehiculos (puerto 8001)
require __DIR__ . '/vendor/autoload.php';

use Vehiculos\BaseDatos;
use Vehiculos\Respuesta;

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
            $st = $pdo->prepare('SELECT * FROM vehiculos WHERE id = ?');
            $st->execute([$id]);
            $v = $st->fetch();
            if (!$v) Respuesta::error('Vehiculo no encontrado', 404);
            Respuesta::json($v);
        }
        if (!empty($_GET['disponibles'])) {
            $fecha = $_GET['disponibles'];
            $sql = "SELECT * FROM vehiculos v WHERE v.estado != 'mantenimiento'
                AND NOT EXISTS (SELECT 1 FROM reservas r WHERE r.vehiculo_id = v.id
                AND r.estado = 'activa' AND ? BETWEEN r.fecha_inicio AND r.fecha_fin)";
            $st = $pdo->prepare($sql);
            $st->execute([$fecha]);
            Respuesta::json($st->fetchAll());
        }
        if (!empty($_GET['estado'])) {
            $st = $pdo->prepare('SELECT * FROM vehiculos WHERE estado = ? ORDER BY id');
            $st->execute([$_GET['estado']]);
            Respuesta::json($st->fetchAll());
        }
        Respuesta::json($pdo->query('SELECT * FROM vehiculos ORDER BY id')->fetchAll());
    }

    if ($metodo === 'POST' || $metodo === 'PUT') {
        $marca = trim($datos['marca'] ?? '');
        $modelo = trim($datos['modelo'] ?? '');
        $anio = $datos['anio'] ?? '';
        $categoria = $datos['categoria'] ?? null;
        $estado = $datos['estado'] ?? 'disponible';
        if ($marca === '' || $modelo === '' || $anio === '') {
            Respuesta::error('Marca, modelo y anio son obligatorios');
        }
        if (!in_array($estado, ['disponible', 'alquilado', 'mantenimiento'])) {
            Respuesta::error('Estado no valido');
        }
        if ($metodo === 'POST') {
            $st = $pdo->prepare('INSERT INTO vehiculos (marca, modelo, anio, categoria, estado) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$marca, $modelo, $anio, $categoria, $estado]);
            Respuesta::json(['id' => (int)$pdo->lastInsertId(), 'mensaje' => 'Vehiculo creado'], 201);
        }
        if (!$id) Respuesta::error('Falta el id');
        $st = $pdo->prepare('SELECT id FROM vehiculos WHERE id = ?');
        $st->execute([$id]);
        if (!$st->fetch()) Respuesta::error('Vehiculo no encontrado', 404);
        $st = $pdo->prepare('UPDATE vehiculos SET marca = ?, modelo = ?, anio = ?, categoria = ?, estado = ? WHERE id = ?');
        $st->execute([$marca, $modelo, $anio, $categoria, $estado, $id]);
        Respuesta::json(['mensaje' => 'Vehiculo actualizado']);
    }

    if ($metodo === 'DELETE') {
        if (!$id) Respuesta::error('Falta el id');
        $st = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE vehiculo_id = ?');
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) Respuesta::error('No se puede borrar, tiene reservas', 409);
        $st = $pdo->prepare('DELETE FROM vehiculos WHERE id = ?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) Respuesta::error('Vehiculo no encontrado', 404);
        Respuesta::json(['mensaje' => 'Vehiculo borrado']);
    }

    Respuesta::error('Metodo no permitido', 405);
} catch (PDOException $e) {
    Respuesta::error('Error en la base de datos', 500);
}
