<?php
namespace Vehiculos;

class Respuesta
{
    // headers de json y cors, y responde las peticiones OPTIONS
    public static function cabeceras()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    // responde con json y termina
    public static function json($datos, $codigo = 200)
    {
        http_response_code($codigo);
        echo json_encode($datos);
        exit;
    }

    public static function error($mensaje, $codigo = 400)
    {
        self::json(['error' => $mensaje], $codigo);
    }
}
