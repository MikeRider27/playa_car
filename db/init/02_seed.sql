INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
    ('Administrador', 'admin@playa.com',    crypt('admin123',    gen_salt('bf', 10)), 'admin'),
    ('Juan Vendedor', 'vendedor@playa.com', crypt('vendedor123', gen_salt('bf', 10)), 'vendedor');

INSERT INTO marcas (nombre) VALUES
    ('Toyota'), ('Nissan'), ('Hyundai'), ('Kia'), ('Chevrolet'),
    ('Volkswagen'), ('Ford'), ('Mitsubishi'), ('Honda'), ('Suzuki');

INSERT INTO vehiculos (marca_id, modelo, anio, color, kilometraje, combustible, transmision, chapa, chasis, precio_compra, precio_venta, estado, descripcion) VALUES
    ((SELECT id FROM marcas WHERE nombre = 'Toyota'),     'Hilux SRV 2.8',     2021, 'Blanco',   58000, 'diesel', 'automatica', 'AAKB 123', '8AJHA3CD0M1234501', 32000, 38500, 'disponible', '4x4, único dueño, service al día.'),
    ((SELECT id FROM marcas WHERE nombre = 'Toyota'),     'Corolla XEI 2.0',   2020, 'Gris',     64000, 'nafta',  'automatica', 'AAHJ 456', '9BRBD3HE0L1234502', 17500, 21900, 'disponible', 'Full equipo, tapizado de cuero.'),
    ((SELECT id FROM marcas WHERE nombre = 'Toyota'),     'Vitz 1.0',          2016, 'Rojo',     92000, 'nafta',  'automatica', 'BCDE 789', 'KSP1301234503',      6800,  8900, 'disponible', 'Ideal para ciudad, muy económico.'),
    ((SELECT id FROM marcas WHERE nombre = 'Nissan'),     'Frontier LE 2.3',   2022, 'Negro',    41000, 'diesel', 'automatica', 'AAPX 321', '3N6CD33B0NK234504', 30000, 35900, 'reservado',  'Doble cabina, 4x4.'),
    ((SELECT id FROM marcas WHERE nombre = 'Hyundai'),    'Tucson GLS 2.0',    2019, 'Azul',     78000, 'nafta',  'automatica', 'AAFG 654', 'KMHJ381ABKU234505', 16000, 19800, 'disponible', 'SUV familiar, techo panorámico.'),
    ((SELECT id FROM marcas WHERE nombre = 'Kia'),        'Sportage LX 2.0',   2018, 'Plata',    85000, 'diesel', 'manual',     'AADR 987', 'KNAPB81ABJ7234506', 14500, 17900, 'disponible', NULL),
    ((SELECT id FROM marcas WHERE nombre = 'Volkswagen'), 'Amarok V6 Highline',2020, 'Gris',     70000, 'diesel', 'automatica', 'AAJK 147', 'WV1ZZZ2HZLA234507', 33000, 39500, 'disponible', 'Motor V6 258 CV.'),
    ((SELECT id FROM marcas WHERE nombre = 'Chevrolet'),  'S10 LTZ 2.8',       2019, 'Blanco',   96000, 'diesel', 'manual',     'AAGH 258', '9BG148FK0KC234508', 21000, 25500, 'disponible', NULL),
    ((SELECT id FROM marcas WHERE nombre = 'Ford'),       'Ranger XLT 3.2',    2018, 'Rojo',    110000, 'diesel', 'automatica', 'AAEF 369', '8AFAR23L0JJ234509', 20000, 24000, 'vendido',    NULL),
    ((SELECT id FROM marcas WHERE nombre = 'Mitsubishi'), 'L200 Triton GLS',   2017, 'Negro',   120000, 'diesel', 'manual',     'AACD 741', 'MMBJNKB40HD234510', 15000, 18500, 'vendido',    NULL),
    ((SELECT id FROM marcas WHERE nombre = 'Honda'),      'Fit EX 1.5',        2019, 'Blanco',   55000, 'nafta',  'automatica', 'AAKL 852', '93HGK5870KZ234511', 10500, 13200, 'disponible', 'Muy espacioso, bajo consumo.'),
    ((SELECT id FROM marcas WHERE nombre = 'Suzuki'),     'Swift GLX 1.2',     2021, 'Amarillo', 30000, 'nafta',  'manual',     'AAMN 963', 'MA3ZC83S0MA234512',  9500, 12400, 'disponible', NULL);

INSERT INTO clientes (nombre, apellido, documento, telefono, email, direccion) VALUES
    ('María',  'González', '4567890', '0981 123 456', 'maria.gonzalez@mail.com', 'Asunción'),
    ('Carlos', 'Benítez',  '3214567', '0971 654 321', 'carlos.benitez@mail.com', 'San Lorenzo'),
    ('Ana',    'Martínez', '5123987', '0991 222 333', NULL,                      'Luque');

INSERT INTO ventas (vehiculo_id, cliente_id, usuario_id, fecha, precio, forma_pago, entrega_inicial, cuotas, observacion) VALUES
    ((SELECT id FROM vehiculos WHERE chapa = 'AAEF 369'), (SELECT id FROM clientes WHERE documento = '4567890'),
     (SELECT id FROM usuarios WHERE email = 'vendedor@playa.com'), CURRENT_DATE - INTERVAL '40 days', 23500, 'contado', NULL, NULL, 'Pago en efectivo.'),
    ((SELECT id FROM vehiculos WHERE chapa = 'AACD 741'), (SELECT id FROM clientes WHERE documento = '3214567'),
     (SELECT id FROM usuarios WHERE email = 'admin@playa.com'), CURRENT_DATE - INTERVAL '5 days', 18500, 'financiado', 6000, 24, NULL);
