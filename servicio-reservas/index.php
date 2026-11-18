<?php
// servicio de reservas (puerto 8003)
require 'conexion.php';

try {
    if ($metodo === 'GET') {
        $sql = "SELECT r.*, c.nombre AS cliente, CONCAT(v.marca, ' ', v.modelo) AS vehiculo
            FROM reservas r
            JOIN clientes c ON c.id = r.cliente_id
            JOIN vehiculos v ON v.id = r.vehiculo_id WHERE 1=1";
        $params = [];
        if (!empty($_GET['cliente_id'])) {
            $sql .= ' AND r.cliente_id = ?';
            $params[] = (int)$_GET['cliente_id'];
        }
        if (!empty($_GET['vehiculo_id'])) {
            $sql .= ' AND r.vehiculo_id = ?';
            $params[] = (int)$_GET['vehiculo_id'];
        }
        $sql .= ' ORDER BY r.id DESC';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        responder($st->fetchAll());
    }

    if ($metodo === 'POST') {
        $cliente = (int)($datos['cliente_id'] ?? 0);
        $vehiculo = (int)($datos['vehiculo_id'] ?? 0);
        $inicio = $datos['fecha_inicio'] ?? '';
        $fin = $datos['fecha_fin'] ?? '';
        if (!$cliente || !$vehiculo || $inicio === '' || $fin === '') {
            error('Faltan datos de la reserva');
        }
        if ($fin < $inicio) error('La fecha fin no puede ser menor a la fecha inicio');

        $st = $pdo->prepare('SELECT id FROM clientes WHERE id = ?');
        $st->execute([$cliente]);
        if (!$st->fetch()) error('Cliente no encontrado', 404);

        $st = $pdo->prepare('SELECT estado FROM vehiculos WHERE id = ?');
        $st->execute([$vehiculo]);
        $v = $st->fetch();
        if (!$v) error('Vehiculo no encontrado', 404);
        if ($v['estado'] === 'mantenimiento') error('El vehiculo esta en mantenimiento', 409);

        $st = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE vehiculo_id = ? AND estado = 'activa'
            AND fecha_inicio <= ? AND fecha_fin >= ?");
        $st->execute([$vehiculo, $fin, $inicio]);
        if ($st->fetchColumn() > 0) error('El vehiculo ya tiene una reserva en esas fechas', 409);

        $st = $pdo->prepare("INSERT INTO reservas (cliente_id, vehiculo_id, fecha_inicio, fecha_fin, estado)
            VALUES (?, ?, ?, ?, 'activa')");
        $st->execute([$cliente, $vehiculo, $inicio, $fin]);
        $nuevo = (int)$pdo->lastInsertId();
        $st = $pdo->prepare("UPDATE vehiculos SET estado = 'alquilado' WHERE id = ?");
        $st->execute([$vehiculo]);
        responder(['id' => $nuevo, 'mensaje' => 'Reserva creada'], 201);
    }

    if ($metodo === 'PUT') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $accion = $_GET['accion'] ?? '';
        if (!$id) error('Falta el id');
        if ($accion !== 'devolver' && $accion !== 'cancelar') {
            error('Accion no valida (devolver o cancelar)');
        }
        $st = $pdo->prepare('SELECT * FROM reservas WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        if (!$r) error('Reserva no encontrada', 404);
        if ($r['estado'] !== 'activa') error('La reserva no esta activa');

        $nuevoEstado = $accion === 'devolver' ? 'completada' : 'cancelada';
        $st = $pdo->prepare('UPDATE reservas SET estado = ? WHERE id = ?');
        $st->execute([$nuevoEstado, $id]);
        $st = $pdo->prepare("UPDATE vehiculos SET estado = 'disponible' WHERE id = ?");
        $st->execute([$r['vehiculo_id']]);
        responder(['mensaje' => "Reserva $nuevoEstado"]);
    }

    error('Metodo no permitido', 405);
} catch (PDOException $e) {
    error('Error en la base de datos', 500);
}
