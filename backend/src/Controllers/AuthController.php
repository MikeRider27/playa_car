<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Jwt;
use App\Validator;

final class AuthController extends Controller
{
    private const TTL = 8 * 3600;

    public function login(Request $req): array
    {
        (new Validator($req->body))->required('email', 'password')->validate();

        $user = $this->fetchOne(
            'SELECT id, nombre, email, rol, password_hash FROM usuarios WHERE email = lower(?) AND activo',
            [trim((string) $req->body['email'])]
        );
        if (!$user || !password_verify((string) $req->body['password'], $user['password_hash'])) {
            throw new HttpException(401, 'Email o contraseña incorrectos.');
        }
        unset($user['password_hash']);

        $token = Jwt::encode([
            'sub' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol'],
            'exp' => time() + self::TTL,
        ], Jwt::secret());

        return ['token' => $token, 'usuario' => $user];
    }

    public function me(Request $req): array
    {
        return $this->fetchOne(
            'SELECT id, nombre, email, rol FROM usuarios WHERE id = ? AND activo',
            [$req->user['sub']]
        ) ?? throw new HttpException(401, 'Usuario inactivo.');
    }
}
