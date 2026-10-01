<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Validator;

final class UsuarioController extends Controller
{
    private const COLUMNS = 'id, nombre, email, rol, activo, created_at';

    public function index(): array
    {
        return $this->fetchAll('SELECT ' . self::COLUMNS . ' FROM usuarios ORDER BY nombre');
    }

    public function store(Request $req): Response
    {
        $data = $this->validated($req->body, true);
        $id = $this->insert('usuarios', $data);
        return new Response($this->find($id), 201);
    }

    public function update(Request $req, int $id): array
    {
        $this->find($id);
        $data = $this->validated($req->body, false);
        if ($id === $req->user['sub'] && ($data['rol'] !== 'admin' || $data['activo'] === 'false')) {
            throw new HttpException(422, 'No podés quitarte el rol de administrador ni desactivarte.');
        }
        $this->updateRow('usuarios', $id, $data);
        return $this->find($id);
    }

    public function destroy(Request $req, int $id): Response
    {
        $this->find($id);
        if ($id === $req->user['sub']) {
            throw new HttpException(422, 'No podés eliminar tu propio usuario.');
        }
        $this->execute('DELETE FROM usuarios WHERE id = ?', [$id]);
        return new Response(null, 204);
    }

    private function find(int $id): array
    {
        return $this->fetchOne('SELECT ' . self::COLUMNS . ' FROM usuarios WHERE id = ?', [$id])
            ?? throw new HttpException(404, 'Usuario no encontrado.');
    }

    private function validated(array $in, bool $creating): array
    {
        $v = (new Validator($in))->required('nombre', 'email', 'rol')->email('email')->in('rol', ['admin', 'vendedor']);
        if ($creating) {
            $v->required('password');
        }
        $v->validate();

        $password = Validator::clean($in['password'] ?? null);
        if ($password !== null && mb_strlen($password) < 6) {
            throw new HttpException(422, 'La contraseña debe tener al menos 6 caracteres.', ['password' => 'Mínimo 6 caracteres.']);
        }

        $data = [
            'nombre' => trim($in['nombre']),
            'email' => mb_strtolower(trim($in['email'])),
            'rol' => $in['rol'],
            'activo' => filter_var($in['activo'] ?? true, FILTER_VALIDATE_BOOL) ? 'true' : 'false',
        ];
        if ($password !== null) {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }
        return $data;
    }
}
