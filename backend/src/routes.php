<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\ClienteController;
use App\Controllers\DashboardController;
use App\Controllers\MarcaController;
use App\Controllers\UsuarioController;
use App\Controllers\VehiculoController;
use App\Controllers\VentaController;
use App\Http\Router;

$r = new Router();

// Controladores instanciados solo cuando se usa la ruta
$c = fn (string $class, string $method) => fn (...$args) => (new $class())->$method(...$args);

$r->add('GET', '/api/health', fn () => ['status' => 'ok'], Router::PUBLIC);

$r->add('POST', '/api/auth/login', $c(AuthController::class, 'login'), Router::PUBLIC);
$r->add('GET', '/api/auth/me', $c(AuthController::class, 'me'));

// Catálogo público
$r->add('GET', '/api/catalogo', $c(VehiculoController::class, 'catalogo'), Router::PUBLIC);
$r->add('GET', '/api/catalogo/{id}', $c(VehiculoController::class, 'catalogoShow'), Router::PUBLIC);
$r->add('GET', '/api/marcas', $c(MarcaController::class, 'index'), Router::PUBLIC);

$r->add('GET', '/api/dashboard', $c(DashboardController::class, 'index'));

$r->add('POST', '/api/marcas', $c(MarcaController::class, 'store'));
$r->add('PUT', '/api/marcas/{id}', $c(MarcaController::class, 'update'));
$r->add('DELETE', '/api/marcas/{id}', $c(MarcaController::class, 'destroy'), Router::ADMIN);

$r->add('GET', '/api/vehiculos', $c(VehiculoController::class, 'index'));
$r->add('GET', '/api/vehiculos/{id}', $c(VehiculoController::class, 'show'));
$r->add('POST', '/api/vehiculos', $c(VehiculoController::class, 'store'));
$r->add('PUT', '/api/vehiculos/{id}', $c(VehiculoController::class, 'update'));
$r->add('DELETE', '/api/vehiculos/{id}', $c(VehiculoController::class, 'destroy'), Router::ADMIN);

$r->add('GET', '/api/clientes', $c(ClienteController::class, 'index'));
$r->add('GET', '/api/clientes/{id}', $c(ClienteController::class, 'show'));
$r->add('POST', '/api/clientes', $c(ClienteController::class, 'store'));
$r->add('PUT', '/api/clientes/{id}', $c(ClienteController::class, 'update'));
$r->add('DELETE', '/api/clientes/{id}', $c(ClienteController::class, 'destroy'), Router::ADMIN);

$r->add('GET', '/api/ventas', $c(VentaController::class, 'index'));
$r->add('GET', '/api/ventas/{id}', $c(VentaController::class, 'show'));
$r->add('POST', '/api/ventas', $c(VentaController::class, 'store'));
$r->add('POST', '/api/ventas/{id}/anular', $c(VentaController::class, 'anular'), Router::ADMIN);

$r->add('GET', '/api/usuarios', $c(UsuarioController::class, 'index'), Router::ADMIN);
$r->add('POST', '/api/usuarios', $c(UsuarioController::class, 'store'), Router::ADMIN);
$r->add('PUT', '/api/usuarios/{id}', $c(UsuarioController::class, 'update'), Router::ADMIN);
$r->add('DELETE', '/api/usuarios/{id}', $c(UsuarioController::class, 'destroy'), Router::ADMIN);

return $r;
