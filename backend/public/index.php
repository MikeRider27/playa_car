<?php

declare(strict_types=1);

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

header('Access-Control-Allow-Origin: ' . (getenv('CORS_ORIGIN') ?: '*'));
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $router = require __DIR__ . '/../src/routes.php';
    $response = $router->dispatch(Request::fromGlobals());
} catch (HttpException $e) {
    $response = new Response(array_filter(['error' => $e->getMessage(), 'errors' => $e->errors]), $e->status);
} catch (PDOException $e) {
    $response = match ($e->getCode()) {
        '23505' => new Response(['error' => 'Ya existe un registro con esos datos (valor duplicado).'], 409),
        '23503' => new Response(['error' => 'No se puede completar: el registro está relacionado con otros datos.'], 409),
        '23514', '22P02', '22003' => new Response(['error' => 'Datos inválidos.'], 422),
        default => new Response(['error' => 'Error de base de datos.'], 500),
    };
    if ($response->status === 500) {
        error_log((string) $e);
    }
} catch (Throwable $e) {
    error_log((string) $e);
    $response = new Response(['error' => 'Error interno del servidor.'], 500);
}

$response->send();
