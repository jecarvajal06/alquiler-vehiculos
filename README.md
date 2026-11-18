# Gestor de Alquiler de Vehiculos

App para registrar vehiculos, clientes y reservas, con devoluciones, historial y consulta de disponibilidad.

Hecha en PHP con microservicios y un frontend en HTML, CSS y JavaScript.

- servicio-vehiculos (puerto 8001)
- servicio-clientes (puerto 8002)
- servicio-reservas (puerto 8003)
- frontend (puerto 8000)

## Como correrlo

1. Importar `database/alquiler_vehiculos_db.sql` en MySQL.
2. Si el usuario o la clave de MySQL no son root sin clave, cambiarlos en el `conexion.php` de cada servicio.
3. Levantar cada servicio en una terminal:

```
php -S localhost:8001 -t servicio-vehiculos
php -S localhost:8002 -t servicio-clientes
php -S localhost:8003 -t servicio-reservas
php -S localhost:8000 -t frontend
```

4. Abrir http://localhost:8000
