<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;

final class DashboardController extends Controller
{
    public function index(Request $req): array
    {
        $stock = $this->fetchOne(
            "SELECT count(*) FILTER (WHERE estado = 'disponible')::int AS disponibles,
                    count(*) FILTER (WHERE estado = 'reservado')::int  AS reservados,
                    count(*) FILTER (WHERE estado = 'vendido')::int    AS vendidos,
                    coalesce(sum(precio_venta) FILTER (WHERE estado <> 'vendido'), 0) AS valor_stock
             FROM vehiculos"
        );

        $mes = $this->fetchOne(
            "SELECT count(s.id)::int AS ventas, coalesce(sum(s.precio), 0) AS monto,
                    coalesce(sum(s.precio - v.precio_compra), 0) AS ganancia
             FROM ventas s JOIN vehiculos v ON v.id = s.vehiculo_id
             WHERE s.estado = 'completada' AND date_trunc('month', s.fecha) = date_trunc('month', CURRENT_DATE)"
        );
        if (!$req->isAdmin()) {
            unset($mes['ganancia']);
        }

        $porMes = $this->fetchAll(
            "SELECT to_char(g.mes, 'YYYY-MM') AS mes, count(s.id)::int AS ventas, coalesce(sum(s.precio), 0) AS monto
             FROM generate_series(date_trunc('month', CURRENT_DATE) - INTERVAL '5 months',
                                  date_trunc('month', CURRENT_DATE), INTERVAL '1 month') AS g(mes)
             LEFT JOIN ventas s ON s.estado = 'completada' AND date_trunc('month', s.fecha) = g.mes
             GROUP BY g.mes ORDER BY g.mes"
        );

        $ultimas = $this->fetchAll(
            "SELECT s.id, s.fecha, s.precio, s.forma_pago, m.nombre || ' ' || v.modelo AS vehiculo,
                    c.nombre || ' ' || c.apellido AS cliente
             FROM ventas s JOIN vehiculos v ON v.id = s.vehiculo_id JOIN marcas m ON m.id = v.marca_id
             JOIN clientes c ON c.id = s.cliente_id
             WHERE s.estado = 'completada' ORDER BY s.fecha DESC, s.id DESC LIMIT 5"
        );

        $clientes = (int) $this->fetchOne('SELECT count(*) AS n FROM clientes')['n'];

        return compact('stock', 'mes', 'porMes', 'ultimas', 'clientes');
    }
}
