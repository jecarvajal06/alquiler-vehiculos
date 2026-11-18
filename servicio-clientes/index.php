<?php
// servicio de clientes (puerto 8002)
require 'conexion.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($metodo === 'GET') {
        if ($id) {
            $st = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
            $st->execute([$id]);
            $c = $st->fetch();
            if (!$c) error('Cliente no encontrado', 404);
            responder($c);
        }
        responder($pdo->query('SELECT * FROM clientes ORDER BY id')->fetchAll());
    }

    if ($metodo === 'POST' || $metodo === 'PUT') {
        $nombre = trim($datos['nombre'] ?? '');
        $telefono = $datos['telefono'] ?? null;
        $correo = $datos['correo'] ?? null;
        $licencia = $datos['numero_licencia'] ?? null;
        if ($nombre === '') error('El nombre es obligatorio');
        if ($metodo === 'POST') {
            $st = $pdo->prepare('INSERT INTO clientes (nombre, telefono, correo, numero_licencia) VALUES (?, ?, ?, ?)');
            $st->execute([$nombre, $telefono, $correo, $licencia]);
            responder(['id' => (int)$pdo->lastInsertId(), 'mensaje' => 'Cliente creado'], 201);
        }
        if (!$id) error('Falta el id');
        $st = $pdo->prepare('SELECT id FROM clientes WHERE id = ?');
        $st->execute([$id]);
        if (!$st->fetch()) error('Cliente no encontrado', 404);
        $st = $pdo->prepare('UPDATE clientes SET nombre = ?, telefono = ?, correo = ?, numero_licencia = ? WHERE id = ?');
        $st->execute([$nombre, $telefono, $correo, $licencia, $id]);
        responder(['mensaje' => 'Cliente actualizado']);
    }

    if ($metodo === 'DELETE') {
        if (!$id) error('Falta el id');
        $st = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE cliente_id = ?');
        $st->execute([$id]);
        if ($st->fetchColumn() > 0) error('No se puede borrar, tiene reservas', 409);
        $st = $pdo->prepare('DELETE FROM clientes WHERE id = ?');
        $st->execute([$id]);
        if ($st->rowCount() === 0) error('Cliente no encontrado', 404);
        responder(['mensaje' => 'Cliente borrado']);
    }

    error('Metodo no permitido', 405);
} catch (PDOException $e) {
    error('Error en la base de datos', 500);
}
