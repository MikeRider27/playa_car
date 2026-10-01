CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS unaccent;

CREATE TABLE usuarios (
    id            SERIAL PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           VARCHAR(20)  NOT NULL DEFAULT 'vendedor' CHECK (rol IN ('admin', 'vendedor')),
    activo        BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE marcas (
    id     SERIAL PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE vehiculos (
    id            SERIAL PRIMARY KEY,
    marca_id      INTEGER       NOT NULL REFERENCES marcas (id) ON DELETE RESTRICT,
    modelo        VARCHAR(80)   NOT NULL,
    anio          SMALLINT      NOT NULL CHECK (anio BETWEEN 1950 AND 2100),
    color         VARCHAR(40),
    kilometraje   INTEGER       NOT NULL DEFAULT 0 CHECK (kilometraje >= 0),
    combustible   VARCHAR(20)   NOT NULL DEFAULT 'nafta'
                  CHECK (combustible IN ('nafta', 'diesel', 'flex', 'hibrido', 'electrico')),
    transmision   VARCHAR(20)   NOT NULL DEFAULT 'manual' CHECK (transmision IN ('manual', 'automatica')),
    chapa         VARCHAR(20) UNIQUE,
    chasis        VARCHAR(40) UNIQUE,
    precio_compra NUMERIC(14, 2) CHECK (precio_compra >= 0),
    precio_venta  NUMERIC(14, 2) NOT NULL CHECK (precio_venta >= 0),
    estado        VARCHAR(20)   NOT NULL DEFAULT 'disponible'
                  CHECK (estado IN ('disponible', 'reservado', 'vendido')),
    descripcion   TEXT,
    imagen_url    TEXT,
    created_at    TIMESTAMPTZ   NOT NULL DEFAULT now(),
    updated_at    TIMESTAMPTZ   NOT NULL DEFAULT now()
);
CREATE INDEX idx_vehiculos_estado ON vehiculos (estado);
CREATE INDEX idx_vehiculos_marca ON vehiculos (marca_id);

CREATE TABLE clientes (
    id         SERIAL PRIMARY KEY,
    nombre     VARCHAR(80)  NOT NULL,
    apellido   VARCHAR(80)  NOT NULL,
    documento  VARCHAR(20)  NOT NULL UNIQUE,
    telefono   VARCHAR(30),
    email      VARCHAR(120),
    direccion  VARCHAR(200),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE TABLE ventas (
    id              SERIAL PRIMARY KEY,
    vehiculo_id     INTEGER        NOT NULL REFERENCES vehiculos (id) ON DELETE RESTRICT,
    cliente_id      INTEGER        NOT NULL REFERENCES clientes (id) ON DELETE RESTRICT,
    usuario_id      INTEGER        NOT NULL REFERENCES usuarios (id) ON DELETE RESTRICT,
    fecha           DATE           NOT NULL DEFAULT CURRENT_DATE,
    precio          NUMERIC(14, 2) NOT NULL CHECK (precio >= 0),
    forma_pago      VARCHAR(20)    NOT NULL CHECK (forma_pago IN ('contado', 'transferencia', 'financiado')),
    entrega_inicial NUMERIC(14, 2) CHECK (entrega_inicial >= 0),
    cuotas          SMALLINT       CHECK (cuotas > 0),
    observacion     TEXT,
    estado          VARCHAR(20)    NOT NULL DEFAULT 'completada' CHECK (estado IN ('completada', 'anulada')),
    created_at      TIMESTAMPTZ    NOT NULL DEFAULT now()
);
-- Un vehículo solo puede tener una venta vigente
CREATE UNIQUE INDEX uq_ventas_vehiculo_vigente ON ventas (vehiculo_id) WHERE estado = 'completada';
CREATE INDEX idx_ventas_fecha ON ventas (fecha);
