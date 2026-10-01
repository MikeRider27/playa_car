<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Validator;

final class VehiculoController extends Controller
{
    public const COMBUSTIBLES = ['nafta', 'diesel', 'flex', 'hibrido', 'electrico'];
    public const TRANSMISIONES = ['manual', 'automatica'];

    private const SELECT = 'SELECT v.*, m.nombre AS marca FROM vehiculos v JOIN marcas m ON m.id = v.marca_id';

    public function index(Request $req): array
    {
        [$where, $params] = $this->filters($req);
        if ($estado = $req->query('estado')) {
            $where[] = 'v.estado = :estado';
            $params['estado'] = $estado;
        }
        $sql = self::SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY v.created_at DESC';
        return array_map(fn ($v) => $this->present($v, $req), $this->fetchAll($sql, $params));
    }

    public function show(Request $req, int $id): array
    {
        return $this->present($this->find($id), $req);
    }

    public function store(Request $req): Response
    {
        $data = $this->validated($req->body);
        if (($data['estado'] ?? 'disponible') === 'vendido') {
            throw new HttpException(422, 'Un vehículo solo pasa a "vendido" registrando una venta.');
        }
        $id = $this->insert('vehiculos', $data);
        return new Response($this->present($this->find($id), $req), 201);
    }

    public function update(Request $req, int $id): array
    {
        $current = $this->find($id);
        $data = $this->validated($req->body);

        if ($current['estado'] === 'vendido' && ($data['estado'] ?? 'vendido') !== 'vendido') {
            throw new HttpException(422, 'El vehículo está vendido. Anulá la venta para cambiar su estado.');
        }
        if ($current['estado'] !== 'vendido' && ($data['estado'] ?? null) === 'vendido') {
            throw new HttpException(422, 'Un vehículo solo pasa a "vendido" registrando una venta.');
        }
        if (!$req->isAdmin()) {
            unset($data['precio_compra']);
        }

        $this->updateRow('vehiculos', $id, [...$data, 'updated_at' => date('c')]);
        return $this->present($this->find($id), $req);
    }

    public function destroy(Request $req, int $id): Response
    {
        $this->find($id);
        $this->execute('DELETE FROM vehiculos WHERE id = ?', [$id]);
        return new Response(null, 204);
    }

    /** Catálogo público: solo vehículos a la venta, sin datos internos. */
    public function catalogo(Request $req): array
    {
        [$where, $params] = $this->filters($req);
        $where[] = "v.estado IN ('disponible', 'reservado')";
        $orden = match ($req->query('orden')) {
            'precio_asc' => 'v.precio_venta ASC',
            'precio_desc' => 'v.precio_venta DESC',
            'anio_desc' => 'v.anio DESC',
            'km_asc' => 'v.kilometraje ASC',
            default => 'v.created_at DESC',
        };
        $rows = $this->fetchAll(self::SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $orden", $params);
        return array_map([$this, 'publico'], $rows);
    }

    public function catalogoShow(Request $req, int $id): array
    {
        $v = $this->fetchOne(self::SELECT . " WHERE v.id = ? AND v.estado IN ('disponible', 'reservado')", [$id])
            ?? throw new HttpException(404, 'Vehículo no encontrado.');
        return $this->publico($v);
    }

    private function filters(Request $req): array
    {
        $where = [];
        $params = [];
        if ($q = $req->query('q')) {
            $where[] = '(unaccent(m.nombre || \' \' || v.modelo) ILIKE unaccent(:q) OR v.chapa ILIKE :q OR unaccent(v.color) ILIKE unaccent(:q))';
            $params['q'] = '%' . $q . '%';
        }
        if ($marca = $req->query('marca_id')) {
            $where[] = 'v.marca_id = :marca_id';
            $params['marca_id'] = (int) $marca;
        }
        if ($min = $req->query('precio_min')) {
            $where[] = 'v.precio_venta >= :precio_min';
            $params['precio_min'] = (float) $min;
        }
        if ($max = $req->query('precio_max')) {
            $where[] = 'v.precio_venta <= :precio_max';
            $params['precio_max'] = (float) $max;
        }
        return [$where, $params];
    }

    private function find(int $id): array
    {
        return $this->fetchOne(self::SELECT . ' WHERE v.id = ?', [$id])
            ?? throw new HttpException(404, 'Vehículo no encontrado.');
    }

    private function validated(array $in): array
    {
        (new Validator($in))
            ->required('marca_id', 'modelo', 'anio', 'precio_venta')
            ->number('anio', 1950, (int) date('Y') + 1)
            ->number('kilometraje')
            ->number('precio_compra')
            ->number('precio_venta')
            ->in('combustible', self::COMBUSTIBLES)
            ->in('transmision', self::TRANSMISIONES)
            ->in('estado', ['disponible', 'reservado', 'vendido'])
            ->validate();

        $upper = fn ($v) => ($v = Validator::clean($v)) === null ? null : mb_strtoupper($v);

        return array_filter([
            'marca_id' => (int) $in['marca_id'],
            'modelo' => trim($in['modelo']),
            'anio' => (int) $in['anio'],
            'color' => Validator::clean($in['color'] ?? null),
            'kilometraje' => (int) ($in['kilometraje'] ?? 0),
            'combustible' => $in['combustible'] ?? 'nafta',
            'transmision' => $in['transmision'] ?? 'manual',
            'chapa' => $upper($in['chapa'] ?? null),
            'chasis' => $upper($in['chasis'] ?? null),
            'precio_compra' => Validator::clean($in['precio_compra'] ?? null),
            'precio_venta' => $in['precio_venta'],
            'estado' => Validator::clean($in['estado'] ?? null),
            'descripcion' => Validator::clean($in['descripcion'] ?? null),
            'imagen_url' => Validator::clean($in['imagen_url'] ?? null),
        ], fn ($v, $k) => $v !== null || !in_array($k, ['estado'], true), ARRAY_FILTER_USE_BOTH);
    }

    private function present(array $v, Request $req): array
    {
        if (!$req->isAdmin()) {
            unset($v['precio_compra']);
        }
        return $v;
    }

    private function publico(array $v): array
    {
        return array_intersect_key($v, array_flip([
            'id', 'marca', 'marca_id', 'modelo', 'anio', 'color', 'kilometraje', 'combustible',
            'transmision', 'precio_venta', 'estado', 'descripcion', 'imagen_url',
        ]));
    }
}
