<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Validator;

final class MarcaController extends Controller
{
    public function index(): array
    {
        return $this->fetchAll(
            'SELECT m.id, m.nombre, count(v.id)::int AS vehiculos
             FROM marcas m LEFT JOIN vehiculos v ON v.marca_id = m.id
             GROUP BY m.id ORDER BY m.nombre'
        );
    }

    public function store(Request $req): Response
    {
        (new Validator($req->body))->required('nombre')->validate();
        $id = $this->insert('marcas', ['nombre' => trim($req->body['nombre'])]);
        return new Response($this->findOrFail('marcas', $id), 201);
    }

    public function update(Request $req, int $id): array
    {
        $this->findOrFail('marcas', $id, 'Marca');
        (new Validator($req->body))->required('nombre')->validate();
        $this->updateRow('marcas', $id, ['nombre' => trim($req->body['nombre'])]);
        return $this->findOrFail('marcas', $id);
    }

    public function destroy(Request $req, int $id): Response
    {
        $this->findOrFail('marcas', $id, 'Marca');
        $this->execute('DELETE FROM marcas WHERE id = ?', [$id]);
        return new Response(null, 204);
    }
}
