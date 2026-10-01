# Playa de Autos 🚗

Sistema de gestión para una playa de venta de autos: stock de vehículos, clientes, ventas (contado o financiadas), dashboard y catálogo público.

- **Backend:** PHP 8.3 + Apache, API REST sin framework (PDO, JWT HS256)
- **Frontend:** Vue 3 + Vite + Vue Router con [AdminLTE 4](https://adminlte.io) (Bootstrap 5) y Bootstrap Icons
- **Base de datos:** PostgreSQL 16
- **Todo dockerizado** con Docker Compose

## Inicio rápido

```bash
cp .env.example .env      # opcional: cambiar contraseñas, JWT_SECRET y puertos
docker compose up -d --build
```

| Servicio | URL |
|---|---|
| Catálogo público | http://localhost:8081 |
| Panel de gestión | http://localhost:8081/panel |
| API (directo) | http://localhost:8000/api/health |
| PostgreSQL | `localhost:5433` (usuario `playa`, clave `playa_secret`, bd `playa_autos`) |

### Usuarios de prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | `admin@playa.com` | `admin123` |
| Vendedor | `vendedor@playa.com` | `vendedor123` |

La base se crea con datos de ejemplo (`db/init/*.sql`) **solo la primera vez**. Para recrearla desde cero:

```bash
docker compose down -v && docker compose up -d
```

## Funcionalidades

- **Catálogo público** (`/`): vehículos disponibles y reservados, con búsqueda, filtros por marca y precio, y orden.
- **Dashboard:** stock por estado, ventas y facturación del mes, valor del stock, gráfico de los últimos 6 meses y últimas ventas. El administrador ve también la ganancia estimada.
- **Vehículos:** alta, edición y baja; estados *disponible / reservado / vendido*; chapa, chasis, costo y precio de venta.
- **Clientes:** alta, edición y baja con historial de compras.
- **Ventas:** registro de la venta al contado, por transferencia o financiada (entrega inicial y cuotas), comprobante imprimible y anulación.
- **Marcas** y **Usuarios** (solo el administrador gestiona usuarios).

### Reglas de negocio

- Un vehículo pasa a *vendido* **solo** al registrar la venta, dentro de una transacción con bloqueo de fila, de modo que no se puede vender dos veces.
- Anular una venta (solo el administrador) vuelve a poner el vehículo como *disponible*.
- El vendedor no ve ni modifica el precio de compra (costo) ni la ganancia.
- No se pueden eliminar clientes con ventas ni marcas con vehículos.
- Las búsquedas ignoran tildes y mayúsculas.

## Estructura

```
├── docker-compose.yml
├── .env.example
├── db/init/                  # esquema y datos iniciales (se ejecutan en el primer arranque)
├── backend/
│   ├── Dockerfile            # php:8.3-apache + pdo_pgsql
│   ├── docker/vhost.conf
│   ├── public/index.php      # front controller
│   └── src/
│       ├── routes.php        # definición de endpoints
│       ├── Http/             # Router, Request, Response, HttpException
│       ├── Controllers/
│       ├── Database.php, Jwt.php, Validator.php
└── frontend/
    ├── Dockerfile            # build con Node + nginx (sirve la SPA y hace proxy de /api)
    ├── nginx.conf
    └── src/
        ├── components/       # AdminLayout (AdminLTE), modales, toasts
        ├── views/            # Catálogo, Login, Dashboard, Vehículos, Clientes, Ventas...
        ├── lib/              # api (axios), auth, formato
        └── router.js
```

## API

Todas las rutas usan el prefijo `/api`. Salvo las públicas, requieren el header `Authorization: Bearer <token>`.

| Método | Ruta | Acceso |
|---|---|---|
| POST | `/auth/login` | público |
| GET | `/auth/me` | usuario |
| GET | `/catalogo`, `/catalogo/{id}`, `/marcas` | público |
| GET | `/dashboard` | usuario |
| POST/PUT | `/marcas`, `/marcas/{id}` | usuario |
| GET/POST/PUT | `/vehiculos`, `/vehiculos/{id}` | usuario |
| GET/POST/PUT | `/clientes`, `/clientes/{id}` | usuario |
| GET/POST | `/ventas`, `/ventas/{id}` | usuario |
| POST | `/ventas/{id}/anular` | admin |
| DELETE | `/marcas/{id}`, `/vehiculos/{id}`, `/clientes/{id}` | admin |
| CRUD | `/usuarios` | admin |

Filtros: `GET /vehiculos?q=&estado=&marca_id=`, `GET /ventas?q=&desde=&hasta=&estado=`, `GET /clientes?q=`, `GET /catalogo?q=&marca_id=&precio_max=&orden=precio_asc|precio_desc|anio_desc|km_asc`.

## Desarrollo del frontend con hot reload

Con la base y el backend corriendo en Docker:

```bash
cd frontend
npm install
npm run dev   # http://localhost:5173 (hace proxy de /api a localhost:8000)
```

## Licencia

Copyright © 2020-present [Miguel Villalba](https://github.com/RiderMike27) 🧔
