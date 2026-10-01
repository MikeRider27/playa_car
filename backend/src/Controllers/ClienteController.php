<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Validator;

final class ClienteController extends Controller
{
    public function index(Request $req): array
    {
        $sql = 'SELECT c.*, (SELECT count(*) FROM ventas s WHERE s.cliente_id = c.id AND s.estado = \'completada\')::int AS compras
                FROM clientes c';
        $params = [];
        if ($q = $req->query('q')) {
            $sql .= " WHERE unaccent(c.nombre || ' ' || c.apellido) ILIKE unaccent(:q)
                      OR unaccent(c.apellido || ' ' || c.nombre) ILIKE unaccent(:q) OR c.documento ILIKE :q";
            $params['q'] = "%$q%";
        }
        return $this->fetchAll($sql . ' ORDER BY c.apellido, c.nombre', $params);
    }

    public function show(Request $req, int $id): array
    {
        $cliente = $this->findOrFail('clientes', $id, 'Cliente');
        $cliente['ventas'] = $this->fetchAll(
            "SELECT s.id, s.fecha, s.precio, s.estado, m.nombre || ' ' || v.modelo AS vehiculo
             FROM ventas s JOIN vehiculos v ON v.id = s.vehiculo_id JOIN marcas m ON m.id = v.marca_id
             WHERE s.cliente_id = ? ORDER BY s.fecha DESC",
            [$id]
        );
        return $cliente;
    }

    public function store(Request $req): Response
    {
        $id = $this->insert('clientes', $this->validated($req->body));
        return new Response($this->findOrFail('clientes', $id), 201);
    }

    public function update(Request $req, int $id): array
    {
        $this->findOrFail('clientes', $id, 'Cliente');
        $this->updateRow('clientes', $id, $this->validated($req->body));
        return $this->findOrFail('clientes', $id);
    }

    public function destroy(Request $req, int $id): Response
    {
        $this->findOrFail('clientes', $id, 'Cliente');
        $this->execute('DELETE FROM clientes WHERE id = ?', [$id]);
        return new Response(null, 204);
    }

    private function validated(array $in): array
    {
        (new Validator($in))->required('nombre', 'apellido', 'documento')->email('email')->validate();
        return [
            'nombre' => trim($in['nombre']),
            'apellido' => trim($in['apellido']),
            'documento' => trim((string) $in['documento']),
            'telefono' => Validator::clean($in['telefono'] ?? null),
            'email' => Validator::clean($in['email'] ?? null),
            'direccion' => Validator::clean($in['direccion'] ?? null),
        ];
    }
}
