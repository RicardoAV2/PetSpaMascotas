-- =====================================================
-- BASE DE DATOS PARA PELUQUERÍA CANINA (VERSIÓN COMPLETA)
-- CUMPLE CON TODOS LOS REQUERIMIENTOS DEL PDF
--DROP DATABASE sap_mascotas;
--CREATE DATABASE sap_mascotas;

-- =====================================================

-- CREAR BASE DE DATOS (OPCIONAL)
CREATE DATABASE IF NOT EXISTS sap_mascotas;
USE sap_mascotas;

-- =====================================================
-- 1. TABLAS DE USUARIOS Y ROLES (BASE PARA AUTOGESTIÓN)
-- =====================================================

CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(100)
);

INSERT INTO rol (nombre, descripcion) VALUES 
('admin', 'Administrador del sistema'),
('recepcion', 'Personal de recepción'),
('groomer', 'Pel uquero/estilista canino'),
('cliente', 'Cliente dueño de mascota');
##ricardoAV123@
CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    telefono VARCHAR(20),
    estado BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL,
    two_factor_secret VARCHAR(255) NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) NULL,
    email_verification_expiry DATETIME NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    id_rol INT NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES rol(id_rol) ON DELETE RESTRICT
);

-- =====================================================
-- 2. CLIENTES (hereda de usuario)
-- =====================================================

CREATE TABLE cliente (
    id_cliente INT PRIMARY KEY,
    direccion VARCHAR(150),
    ci VARCHAR(20),
    canal_notificacion_preferido ENUM('email', 'whatsapp', 'sms') DEFAULT 'email',
    horario_preferido VARCHAR(50),
    recibe_promociones BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- =====================================================
-- 3. GROOMERS (hereda de usuario)
-- =====================================================

CREATE TABLE groomer (
    id_groomer INT PRIMARY KEY,
    especialidad VARCHAR(100),
    capacidad_simultanea INT DEFAULT 1,
    estado_activo BOOLEAN DEFAULT TRUE,
    calificacion_promedio DECIMAL(3,2) DEFAULT 0,
    horario_trabajo TEXT, -- Guarda configuración semanal
    FOREIGN KEY (id_groomer) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- =====================================================
-- 4. RECEPCIONISTAS Y ADMINISTRADORES (opcional, heredan de usuario)
-- =====================================================

CREATE TABLE recepcionista (
    id_recepcionista INT PRIMARY KEY,
    turno VARCHAR(20),
    idiomas VARCHAR(100),
    experiencia INT,
    FOREIGN KEY (id_recepcionista) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE administrador (
    id_administrador INT PRIMARY KEY,
    nivel_acceso INT DEFAULT 1,
    area_responsabilidad VARCHAR(50),
    puede_contratar BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_administrador) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- =====================================================
-- 5. DISPONIBILIDAD Y BLOQUEOS DE AGENDA
-- =====================================================

CREATE TABLE disponibilidad (
    id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    id_groomer INT NOT NULL,
    dia_semana INT NOT NULL, -- 0=Domingo, 1=Lunes,...6=Sábado
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    intervalo_descanso TEXT, -- pausa de almuerzo
    FOREIGN KEY (id_groomer) REFERENCES groomer(id_groomer) ON DELETE CASCADE
    -- CHECK (hora_inicio < hora_fin),
    -- CHECK (dia_semana BETWEEN 0 AND 6)
);

CREATE TABLE bloqueo_agenda (
    id_bloqueo INT AUTO_INCREMENT PRIMARY KEY,
    id_groomer INT NULL, -- NULL = bloqueo global (todos los groomers)
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    motivo VARCHAR(100),
    tipo_bloqueo ENUM('feriado', 'vacaciones', 'mantenimiento', 'ausencia') DEFAULT 'ausencia',
    FOREIGN KEY (id_groomer) REFERENCES groomer(id_groomer) ON DELETE CASCADE
    -- CHECK (fecha_fin > fecha_inicio)
);

-- =====================================================
-- 6. MASCOTAS (con soporte para múltiples dueños)
-- =====================================================

CREATE TABLE mascota (
    id_mascota INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    especie VARCHAR(50) DEFAULT 'Perro',
    raza VARCHAR(50),
    peso DECIMAL(5,2),
    fecha_nacimiento DATE,
    edad INT,
    alergias TEXT,
    comportamiento TEXT,
    vacunas TEXT,
    temperamento VARCHAR(50),
    restricciones_medicas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_cliente_principal INT NOT NULL, -- dueño principal
    FOREIGN KEY (id_cliente_principal) REFERENCES cliente(id_cliente) ON DELETE CASCADE
);


-- Tabla intermedia para múltiples dueños (una mascota puede tener varios dueños)
CREATE TABLE mascota_dueno (
    id_mascota INT,
    id_cliente INT,
    es_principal BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id_mascota, id_cliente),
    FOREIGN KEY (id_mascota) REFERENCES mascota(id_mascota) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE
);

-- =====================================================
-- 7. SERVICIOS (con ajustes por tamaño/raza y doble booking)
-- =====================================================

DROP TABLE IF EXISTS servicio;
CREATE TABLE servicio (
    id_servicio INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    duracion_base_minutos INT NOT NULL,
    precio_base DECIMAL(10,2) NOT NULL,
    permite_doble_booking BOOLEAN DEFAULT FALSE,
    factor_tamaño_raza TEXT, -- Ej: {"pequeño":1, "mediano":1.15, "grande":1.3}
    consumo_insumos TEXT, -- Productos que consume por defecto
    requiere_bloqueo_consecutivo BOOLEAN DEFAULT FALSE,
    estado_activo BOOLEAN DEFAULT TRUE
    -- CHECK (duracion_base_minutos > 0 AND duracion_base_minutos % 15 = 0),
    -- CHECK (precio_base >= 0)
);

-- =====================================================
-- 8. CITAS (con control de reprogramación y buffer)
-- =====================================================

CREATE TABLE cita (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    duracion_real INT NULL, -- minutos reales
    estado ENUM('agendada', 'confirmada', 'en_progreso', 'completada', 'cancelada', 'no_asistio') DEFAULT 'agendada',
    creado_por INT NOT NULL, -- usuario que creó la cita (admin, recepción o cliente)
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_reprogramacion TIMESTAMP NULL,
    usuario_reprogramo INT NULL,
    motivo_cancelacion VARCHAR(200),
    id_mascota INT NOT NULL,
    id_groomer INT NOT NULL,
    id_servicio INT NOT NULL,
    FOREIGN KEY (id_mascota) REFERENCES mascota(id_mascota) ON DELETE RESTRICT,
    FOREIGN KEY (id_groomer) REFERENCES groomer(id_groomer) ON DELETE RESTRICT,
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE RESTRICT,
    FOREIGN KEY (creado_por) REFERENCES usuario(id_usuario),
    FOREIGN KEY (usuario_reprogramo) REFERENCES usuario(id_usuario),
    -- CHECK (fecha_fin > fecha_inicio),
    UNIQUE KEY unique_cita_groomer_horario (id_groomer, fecha_inicio) -- Evita solapamientos
);

-- =====================================================
-- 9. FICHA DE GROOMING (servicio realizado)
-- =====================================================

CREATE TABLE ficha_grooming (
    id_ficha INT AUTO_INCREMENT PRIMARY KEY,
    hora_inicio DATETIME NOT NULL,
    hora_fin_real DATETIME,
    temperatura_animal DECIMAL(4,1), -- temperatura corporal al ingreso
    peso_kg DECIMAL(5,2), -- peso al momento del servicio
    raza_talla VARCHAR(50), -- datos actualizados de la mascota
    observaciones TEXT,
    notas_internas TEXT, -- solo para el equipo
    estado_mascota TEXT,
    consumido_inventario BOOLEAN DEFAULT FALSE, -- flag si ya se descontaron insumos
    fecha_cierre DATETIME NULL,
    id_cita INT UNIQUE NOT NULL,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE CASCADE
);

-- =====================================================
-- 10. CHECKLIST (estandarizado por servicio)
-- =====================================================

CREATE TABLE checklist_item (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_observacion BOOLEAN DEFAULT FALSE
);

-- Template de checklist por servicio (qué items debe tener cada tipo de servicio)
CREATE TABLE servicio_checklist_template (
    id_servicio INT,
    id_item INT,
    orden INT DEFAULT 0,
    PRIMARY KEY (id_servicio, id_item),
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE,
    FOREIGN KEY (id_item) REFERENCES checklist_item(id_item) ON DELETE CASCADE
);

-- Registro de checklist completado en cada ficha de grooming
CREATE TABLE ficha_checklist (
    id_ficha INT,
    id_item INT,
    completado BOOLEAN DEFAULT FALSE,
    observacion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_ficha, id_item),
    FOREIGN KEY (id_ficha) REFERENCES ficha_grooming(id_ficha) ON DELETE CASCADE,
    FOREIGN KEY (id_item) REFERENCES checklist_item(id_item) ON DELETE CASCADE
);

-- =====================================================
-- 11. FOTOS (antes/después)
-- =====================================================

CREATE TABLE foto (
    id_foto INT AUTO_INCREMENT PRIMARY KEY,
    url TEXT NOT NULL,
    tipo ENUM('antes', 'despues') NOT NULL,
    descripcion VARCHAR(200),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_ficha INT NOT NULL,
    FOREIGN KEY (id_ficha) REFERENCES ficha_grooming(id_ficha) ON DELETE CASCADE
);

-- =====================================================
-- 12. PRODUCTOS, CATEGORÍAS y VARIANTES
-- =====================================================

CREATE TABLE categoria_producto (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    id_padre INT NULL,
    FOREIGN KEY (id_padre) REFERENCES categoria_producto(id_categoria) ON DELETE SET NULL
);

CREATE TABLE producto (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2) NOT NULL,
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    estado_activo BOOLEAN DEFAULT TRUE,
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES categoria_producto(id_categoria) ON DELETE SET NULL
    -- CHECK (stock_actual >= 0),
    -- CHECK (precio_base >= 0)
);

-- Variantes de producto (talla, fragancia, tamaño, etc.)
CREATE TABLE variante_producto (
    id_variante INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    atributo VARCHAR(50) NOT NULL, -- "tamaño", "fragancia", "talla"
    valor VARCHAR(50) NOT NULL,    -- "1kg", "lavanda", "S"
    precio_extra DECIMAL(10,2) DEFAULT 0,
    stock_adicional INT DEFAULT 0,
    sku_variante VARCHAR(50) UNIQUE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE
    -- CHECK (precio_extra >= 0),
    -- CHECK (stock_adicional >= 0)
);

-- =====================================================
-- 13. CARRITO / PEDIDO (con soporte para WhatsApp/Telegram)
-- =====================================================

CREATE TABLE carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    id_cliente INT NULL, -- NULL para carritos anónimos
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL, -- expiración (ej: 7 días)
    metodo_contacto ENUM('whatsapp', 'telegram', 'email') DEFAULT 'whatsapp',
    contacto_destino VARCHAR(100), -- número de teléfono o email
    estado_pedido ENUM('pendiente', 'enviado', 'confirmado', 'pagado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    subtotal DECIMAL(10,2) DEFAULT 0,
    descuento DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE SET NULL
    -- CHECK (expires_at > fecha_creacion)
);

CREATE TABLE detalle_carrito (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT NOT NULL,
    id_producto INT NOT NULL,
    id_variante INT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL, -- precio congelado al momento de agregar
    FOREIGN KEY (id_carrito) REFERENCES carrito(id_carrito) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT,
    FOREIGN KEY (id_variante) REFERENCES variante_producto(id_variante) ON DELETE SET NULL
);

-- =====================================================
-- 14. FACTURACIÓN (con pagos parciales)
-- =====================================================

CREATE TABLE factura (
    id_factura INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(20) UNIQUE NOT NULL,
    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    impuesto DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado_factura ENUM('pendiente', 'pagada', 'cancelada') DEFAULT 'pendiente',
    id_cita INT NULL,
    id_pedido INT NULL, -- referencia al carrito/pedido
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE SET NULL,
    FOREIGN KEY (id_pedido) REFERENCES carrito(id_carrito) ON DELETE SET NULL
    -- CHECK (total = subtotal + impuesto)
);

-- Detalle de factura (productos o servicios)
CREATE TABLE detalle_factura (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_factura INT NOT NULL,
    concepto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    id_producto INT NULL,
    id_servicio INT NULL,
    FOREIGN KEY (id_factura) REFERENCES factura(id_factura) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE SET NULL,
    FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE SET NULL
    -- CHECK (cantidad > 0),
    -- CHECK (precio_unitario >= 0)
);

-- Pagos (soporta pagos parciales: una factura puede tener múltiples pagos)
CREATE TABLE pago (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'qr', 'transferencia', 'tarjeta') NOT NULL,
    referencia_transaccion VARCHAR(100),
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_pago ENUM('completado', 'pendiente', 'fallido') DEFAULT 'completado',
    id_factura INT NOT NULL,
    FOREIGN KEY (id_factura) REFERENCES factura(id_factura) ON DELETE CASCADE,
    CHECK (monto > 0)
);

-- =====================================================
-- 15. CONSUMO DE INSUMOS (productos usados en grooming)
-- =====================================================

CREATE TABLE uso_producto (
    id_uso INT AUTO_INCREMENT PRIMARY KEY,
    id_ficha INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    fecha_uso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ficha) REFERENCES ficha_grooming(id_ficha) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT,
    CHECK (cantidad > 0)
);

-- =====================================================
-- 16. NOTIFICACIONES (con log de envíos y reintentos)
-- =====================================================

CREATE TABLE notificacion (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    tipo_evento ENUM('confirmacion', 'recordatorio_24h', 'recordatorio_2h', 'listo_recoger', 'encuesta', 'promocion') NOT NULL,
    canal ENUM('email', 'whatsapp', 'sms') NOT NULL,
    mensaje TEXT NOT NULL,
    destino VARCHAR(100) NOT NULL, -- email o número teléfono
    fecha_programacion DATETIME NOT NULL,
    fecha_envio DATETIME NULL,
    estado_envio ENUM('pendiente', 'enviado', 'fallido', 'reintentando') DEFAULT 'pendiente',
    reintentos INT DEFAULT 0,
    id_cliente INT NULL,
    id_cita INT NULL,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE SET NULL,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE SET NULL
);

-- =====================================================
-- 17. CALIFICACIONES Y ENCUESTAS POST-SERVICIO
-- =====================================================

CREATE TABLE calificacion (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    puntuacion INT NOT NULL CHECK (puntuacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_cliente INT NOT NULL,
    id_groomer INT NOT NULL,
    id_cita INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (id_groomer) REFERENCES groomer(id_groomer) ON DELETE CASCADE,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE CASCADE,
    UNIQUE KEY unique_calificacion_cita (id_cita) -- Una calificación por cita
);

-- =====================================================
-- 18. HISTORIAL DE MASCOTAS
-- =====================================================

CREATE TABLE historial_mascota (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    tipo_evento ENUM('servicio', 'recomendacion', 'alerta', 'nota_medica') NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_mascota INT NOT NULL,
    id_cita INT NULL,
    FOREIGN KEY (id_mascota) REFERENCES mascota(id_mascota) ON DELETE CASCADE,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE SET NULL
);

-- =====================================================
-- 19. SESIONES DE USUARIO (seguridad: JWT, refresh token)
-- =====================================================

CREATE TABLE usuario_sesion (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token_jwt TEXT NOT NULL,
    refresh_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- =====================================================
-- ENTIDAD INVENTARIO (CONTROL CENTRAL DE STOCK)
-- =====================================================

-- 1. TABLA PRINCIPAL DE INVENTARIO (control de stock por producto/lote)
CREATE TABLE inventario (
    id_inventario INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_variante INT NULL, -- si el producto tiene variantes (talla, fragancia)
    cantidad_fisica INT NOT NULL DEFAULT 0, -- stock real en bodega
    cantidad_reservada INT NOT NULL DEFAULT 0, -- apartado para citas o pedidos
    cantidad_disponible INT GENERATED ALWAYS AS (cantidad_fisica - cantidad_reservada) STORED,
    ubicacion VARCHAR(100), -- estante, bodega, etc.
    fecha_vencimiento DATE NULL, -- para productos perecibles
    estado_lote ENUM('activo', 'agotado', 'vencido', 'bloqueado') DEFAULT 'activo',
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT,
    FOREIGN KEY (id_variante) REFERENCES variante_producto(id_variante) ON DELETE SET NULL,
    CHECK (cantidad_fisica >= 0),
    CHECK (cantidad_reservada >= 0),
    CHECK (cantidad_reservada <= cantidad_fisica)
);

-- 2. MOVIMIENTOS DE INVENTARIO (registro detallado de cada transacción)
CREATE TABLE movimiento_inventario (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT NOT NULL,
    tipo_movimiento ENUM('entrada_compra', 'entrada_devolucion', 'entrada_ajuste', 
                         'salida_venta', 'salida_consumo', 'salida_perdida', 
                         'reserva', 'liberacion_reserva') NOT NULL,
    cantidad INT NOT NULL,
    cantidad_fisica_antes INT NOT NULL,
    cantidad_fisica_despues INT NOT NULL,
    cantidad_reservada_antes INT NOT NULL,
    cantidad_reservada_despues INT NOT NULL,
    referencia_tipo ENUM('cita', 'pedido', 'factura', 'ajuste') NULL,
    referencia_id INT NULL, -- id_cita, id_pedido, id_factura, etc.
    motivo VARCHAR(200),
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario_registra INT NOT NULL, -- quién hizo el movimiento
    FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario_registra) REFERENCES usuario(id_usuario) ON DELETE RESTRICT,
    CHECK (cantidad > 0)
);

-- 3. ALERTAS DE INVENTARIO (bajo stock, vencimiento, etc.)
CREATE TABLE alerta_inventario (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT NOT NULL,
    tipo_alerta ENUM('bajo_stock', 'stock_critico', 'vencimiento_proximo', 'vencido', 'sin_movimiento') NOT NULL,
    mensaje TEXT,
    fecha_alerta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_atencion TIMESTAMP NULL,
    atendido_por INT NULL,
    estado_alerta ENUM('pendiente', 'atendida', 'ignorada') DEFAULT 'pendiente',
    FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario) ON DELETE CASCADE,
    FOREIGN KEY (atendido_por) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);

-- 4. RESERVAS DE INVENTARIO (para citas programadas)
CREATE TABLE reserva_inventario (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_inventario INT NOT NULL,
    id_cita INT NULL, -- si la reserva es para una cita de grooming
    id_pedido INT NULL, -- si la reserva es para un pedido en carrito
    cantidad INT NOT NULL,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_liberacion TIMESTAMP NULL,
    estado_reserva ENUM('activa', 'consumida', 'liberada', 'cancelada') DEFAULT 'activa',
    FOREIGN KEY (id_inventario) REFERENCES inventario(id_inventario) ON DELETE CASCADE,
    FOREIGN KEY (id_cita) REFERENCES cita(id_cita) ON DELETE CASCADE,
    FOREIGN KEY (id_pedido) REFERENCES carrito(id_carrito) ON DELETE CASCADE,
    CHECK (cantidad > 0)
);

-- 5. REORDENES DE INVENTARIO (compras automáticas sugeridas)
CREATE TABLE reorden_inventario (
    id_reorden INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    cantidad_sugerida INT NOT NULL,
    fecha_sugerencia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_compra_realizada TIMESTAMP NULL,
    comprado_por INT NULL,
    estado ENUM('pendiente', 'aprobado', 'comprado', 'cancelado') DEFAULT 'pendiente',
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE,
    FOREIGN KEY (comprado_por) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);

-- =====================================================
-- ÍNDICES PARA OPTIMIZAR CONSULTAS
-- =====================================================

CREATE INDEX idx_cita_fecha ON cita(fecha_inicio, estado);
CREATE INDEX idx_cita_groomer_estado ON cita(id_groomer, estado);
CREATE INDEX idx_cita_mascota ON cita(id_mascota);
CREATE INDEX idx_notificacion_programacion ON notificacion(fecha_programacion, estado_envio);
CREATE INDEX idx_producto_stock ON producto(stock_actual, stock_minimo);
CREATE INDEX idx_factura_cliente ON factura(id_cita);
CREATE INDEX idx_pago_factura ON pago(id_factura, estado_pago);
CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_sesion_token ON usuario_sesion(refresh_token);

-- =====================================================
-- TRIGGERS PARA CONTROL AUTOMÁTICO DE STOCK
-- =====================================================

-- Trigger: Actualizar inventario cuando se consume producto en ficha de grooming
DELIMITER $$
CREATE TRIGGER after_uso_producto
AFTER INSERT ON uso_producto
FOR EACH ROW
BEGIN
    DECLARE inv_id INT DEFAULT NULL;
    DECLARE cantidad_antes INT DEFAULT 0;
    DECLARE ficha_cita INT DEFAULT NULL;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET inv_id = NULL;

    SELECT id_cita INTO ficha_cita
    FROM ficha_grooming
    WHERE id_ficha = NEW.id_ficha
    LIMIT 1;

    SELECT id_inventario, cantidad_fisica
    INTO inv_id, cantidad_antes
    FROM inventario
    WHERE id_producto = NEW.id_producto
      AND estado_lote = 'activo'
    ORDER BY cantidad_fisica DESC
    LIMIT 1;

    IF inv_id IS NOT NULL THEN
        UPDATE inventario
        SET cantidad_fisica = cantidad_fisica - NEW.cantidad
        WHERE id_inventario = inv_id;

        INSERT INTO movimiento_inventario (
            id_inventario, tipo_movimiento, cantidad,
            cantidad_fisica_antes, cantidad_fisica_despues,
            cantidad_reservada_antes, cantidad_reservada_despues,
            referencia_tipo, referencia_id, id_usuario_registra
        ) VALUES (
            inv_id, 'salida_consumo', NEW.cantidad,
            cantidad_antes, cantidad_antes - NEW.cantidad,
            0, 0,
            'cita', ficha_cita, 1
        );

        IF (SELECT cantidad_fisica FROM inventario WHERE id_inventario = inv_id) <=
           (SELECT stock_minimo FROM producto WHERE id_producto = NEW.id_producto) THEN
            INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje)
            VALUES (inv_id, 'bajo_stock', CONCAT('Stock bajo del producto: ',
                   (SELECT nombre FROM producto WHERE id_producto = NEW.id_producto)));
        END IF;
    ELSE
        INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje)
        VALUES (NULL, 'stock_critico', CONCAT('Sin inventario activo para producto ID: ', NEW.id_producto));
    END IF;
END$$

-- Trigger: Reservar inventario al crear cita
-- CREATE TRIGGER after_insert_cita_reserva
-- AFTER INSERT ON cita
-- FOR EACH ROW
-- BEGIN
--     DECLARE done INT DEFAULT FALSE;
--     DECLARE prod_id INT;
--     DECLARE cant INT;
--     DECLARE inv_id INT DEFAULT NULL;
--     DECLARE cur CURSOR FOR
--         SELECT id_producto, cantidad FROM servicio_consumo_insumos
--         WHERE id_servicio = NEW.id_servicio;
--     DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
-- 
--     OPEN cur;
--     read_loop: LOOP
--         FETCH cur INTO prod_id, cant;
--         IF done THEN
--             LEAVE read_loop;
--         END IF;
-- 
--         SET inv_id = NULL;
--         SELECT id_inventario INTO inv_id
--         FROM inventario
--         WHERE id_producto = prod_id
--           AND cantidad_disponible >= cant
--         ORDER BY cantidad_disponible DESC
--         LIMIT 1;
-- 
--         IF inv_id IS NOT NULL THEN
--             INSERT INTO reserva_inventario (id_inventario, id_cita, cantidad, estado_reserva)
--             VALUES (inv_id, NEW.id_cita, cant, 'activa');
-- 
--             UPDATE inventario
--             SET cantidad_reservada = cantidad_reservada + cant
--             WHERE id_inventario = inv_id;
--         ELSE
--             INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje)
--             VALUES (NULL, 'stock_critico',
--                     CONCAT('Sin stock suficiente para cita #', NEW.id_cita,
--                            ' producto ID: ', prod_id));
--         END IF;
--     END LOOP;
--     CLOSE cur;
-- END$$

CREATE TRIGGER trig_mascota_before_insert
BEFORE INSERT ON mascota
FOR EACH ROW
BEGIN
    SET NEW.edad = CASE
        WHEN NEW.fecha_nacimiento IS NULL THEN NULL
        ELSE YEAR(CURDATE()) - YEAR(NEW.fecha_nacimiento) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(NEW.fecha_nacimiento, '%m%d'))
    END;
END$$

CREATE TRIGGER trig_mascota_before_update
BEFORE UPDATE ON mascota
FOR EACH ROW
BEGIN
    SET NEW.edad = CASE
        WHEN NEW.fecha_nacimiento IS NULL THEN NULL
        ELSE YEAR(CURDATE()) - YEAR(NEW.fecha_nacimiento) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(NEW.fecha_nacimiento, '%m%d'))
    END;
END$$
DELIMITER ;

-- =====================================================
-- RESET AUTO_INCREMENT PARA TODAS LAS TABLAS
-- =====================================================

ALTER TABLE rol AUTO_INCREMENT = 1;
ALTER TABLE usuario AUTO_INCREMENT = 1;
ALTER TABLE disponibilidad AUTO_INCREMENT = 1;
ALTER TABLE bloqueo_agenda AUTO_INCREMENT = 1;
ALTER TABLE mascota AUTO_INCREMENT = 1;
ALTER TABLE servicio AUTO_INCREMENT = 1;
ALTER TABLE cita AUTO_INCREMENT = 1;
ALTER TABLE ficha_grooming AUTO_INCREMENT = 1;
ALTER TABLE checklist_item AUTO_INCREMENT = 1;
ALTER TABLE foto AUTO_INCREMENT = 1;
ALTER TABLE categoria_producto AUTO_INCREMENT = 1;
ALTER TABLE producto AUTO_INCREMENT = 1;
ALTER TABLE variante_producto AUTO_INCREMENT = 1;
ALTER TABLE carrito AUTO_INCREMENT = 1;
ALTER TABLE detalle_carrito AUTO_INCREMENT = 1;
ALTER TABLE factura AUTO_INCREMENT = 1;
ALTER TABLE detalle_factura AUTO_INCREMENT = 1;
ALTER TABLE pago AUTO_INCREMENT = 1;
ALTER TABLE uso_producto AUTO_INCREMENT = 1;
ALTER TABLE notificacion AUTO_INCREMENT = 1;
ALTER TABLE calificacion AUTO_INCREMENT = 1;
ALTER TABLE historial_mascota AUTO_INCREMENT = 1;
ALTER TABLE usuario_sesion AUTO_INCREMENT = 1;
ALTER TABLE inventario AUTO_INCREMENT = 1;
ALTER TABLE movimiento_inventario AUTO_INCREMENT = 1;
ALTER TABLE alerta_inventario AUTO_INCREMENT = 1;
ALTER TABLE reserva_inventario AUTO_INCREMENT = 1;
ALTER TABLE reorden_inventario AUTO_INCREMENT = 1;