<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Validator;
use Throwable;

final class VentaController extends Controller
{
    private const SELECT = "SELECT s.*, m.nombre AS marca, v.modelo, v.anio, v.chapa, v.color,
            c.nombre || ' ' || c.apellido AS cliente, c.documento AS cliente_documento,
            c.telefono AS cliente_telefono, u.nombre AS vendedor
        FROM ventas s
        JOIN vehiculos v ON v.id = s.vehiculo_id
        JOIN marcas m ON m.id = v.marca_id
        JOIN clientes c ON c.id = s.cliente_id
        JOIN usuarios u ON u.id = s.usuario_id";

    public function index(Request $req): array
    {
        $where = [];
        $params = [];
        if ($desde = $req->query('desde')) {
            $where[] = 's.fecha >= :desde';
            $params['desde'] = $desde;
        }
        if ($hasta = $req->query('hasta')) {
            $where[] = 's.fecha <= :hasta';
            $params['hasta'] = $hasta;
        }
        if ($estado = $req->query('estado')) {
            $where[] = 's.estado = :estado';
            $params['estado'] = $estado;
        }
        if ($q = $req->query('q')) {
            $where[] = "(unaccent(c.nombre || ' ' || c.apellido) ILIKE unaccent(:q) OR c.documento ILIKE :q OR v.modelo ILIKE :q OR m.nombre ILIKE :q OR v.chapa ILIKE :q)";
            $params['q'] = "%$q%";
        }
        $sql = self::SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY s.fecha DESC, s.id DESC';
        return $this->fetchAll($sql, $params);
    }

    public function show(Request $req, int $id): array
    {
        return $this->fetchOne(self::SELECT . ' WHERE s.id = ?', [$id])
            ?? throw new HttpException(404, 'Venta no encontrada.');
    }

    public function store(Request $req): Response
    {
        $in = $req->body;
        (new Validator($in))
            ->required('vehiculo_id', 'cliente_id', 'precio', 'forma_pago')
            ->number('precio')
            ->number('entrega_inicial')
            ->number('cuotas', 1, 360)
            ->in('forma_pago', ['contado', 'transferencia', 'financiado'])
            ->validate();

        $financiado = $in['forma_pago'] === 'financiado';
        if ($financiado && empty($in['cuotas'])) {
            throw new HttpException(422, 'Indicá la cantidad de cuotas.', ['cuotas' => 'Obligatorio para ventas financiadas.']);
        }
        if ($financiado && (float) ($in['entrega_inicial'] ?? 0) > (float) $in['precio']) {
            throw new HttpException(422, 'La entrega inicial no puede superar el precio.', ['entrega_inicial' => 'Mayor al precio.']);
        }

        $this->db->beginTransaction();
        try {
            $vehiculo = $this->fetchOne('SELECT id, estado FROM vehiculos WHERE id = ? FOR UPDATE', [(int) $in['vehiculo_id']])
                ?? throw new HttpException(404, 'Vehículo no encontrado.');
            if ($vehiculo['estado'] === 'vendido') {
                throw new HttpException(409, 'El vehículo ya fue vendido.');
            }
            $this->findOrFail('clientes', (int) $in['cliente_id'], 'Cliente');

            $id = $this->insert('ventas', [
                'vehiculo_id' => (int) $in['vehiculo_id'],
                'cliente_id' => (int) $in['cliente_id'],
                'usuario_id' => $req->user['sub'],
                'fecha' => Validator::clean($in['fecha'] ?? null) ?? date('Y-m-d'),
                'precio' => $in['precio'],
                'forma_pago' => $in['forma_pago'],
                'entrega_inicial' => $financiado ? (Validator::clean($in['entrega_inicial'] ?? null) ?? 0) : null,
                'cuotas' => $financiado ? (int) $in['cuotas'] : null,
                'observacion' => Validator::clean($in['observacion'] ?? null),
            ]);
            $this->execute("UPDATE vehiculos SET estado = 'vendido', updated_at = now() WHERE id = ?", [$vehiculo['id']]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return new Response($this->show($req, $id), 201);
    }

    public function anular(Request $req, int $id): array
    {
        $this->db->beginTransaction();
        try {
            $venta = $this->fetchOne('SELECT * FROM ventas WHERE id = ? FOR UPDATE', [$id])
                ?? throw new HttpException(404, 'Venta no encontrada.');
            if ($venta['estado'] === 'anulada') {
                throw new HttpException(409, 'La venta ya está anulada.');
            }
            $this->execute("UPDATE ventas SET estado = 'anulada' WHERE id = ?", [$id]);
            $this->execute("UPDATE vehiculos SET estado = 'disponible', updated_at = now() WHERE id = ?", [$venta['vehiculo_id']]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $this->show($req, $id);
    }
}
