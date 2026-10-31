-- Konstruktor — Esquema completo (Fase 1: cotización · Fase 2: estimaciones y control de obra · Fase 3: app móvil y avance · Fase 4: migraciones automáticas)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja DATOS: parámetros generales de la empresa
CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    rfc VARCHAR(13) DEFAULT NULL,
    contacto VARCHAR(150) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja BASICOS (mano de obra): cuadrillas e integrantes
CREATE TABLE IF NOT EXISTS cuadrillas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cuadrilla_integrantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuadrilla_id INT NOT NULL,
    puesto VARCHAR(100) NOT NULL,
    cantidad DECIMAL(8,2) NOT NULL DEFAULT 1,
    salario_semanal DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (cuadrilla_id) REFERENCES cuadrillas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja BASICOS: materiales (MAT), mano de obra (MO, una por cuadrilla) y equipo (EQ)
CREATE TABLE IF NOT EXISTS insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(30) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    unidad VARCHAR(20) NOT NULL,
    tipo ENUM('MAT','MO','EQ') NOT NULL DEFAULT 'MAT',
    categoria VARCHAR(100) DEFAULT NULL,
    precio_captura DECIMAL(12,2) NOT NULL DEFAULT 0,
    mas_iva TINYINT(1) NOT NULL DEFAULT 0,          -- 1 = el precio se cotizó sin IVA y se le suma
    precio DECIMAL(12,2) NOT NULL DEFAULT 0,        -- precio que se usa en los análisis
    cuadrilla_id INT DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cuadrilla_id) REFERENCES cuadrillas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja DATOS + CATALOGO: una cotización por obra
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NOT NULL UNIQUE,
    cliente_id INT DEFAULT NULL,
    obra VARCHAR(255) NOT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    municipio VARCHAR(120) DEFAULT NULL,
    fecha_cotizacion DATE NOT NULL,
    vigencia_dias INT NOT NULL DEFAULT 30,
    fecha_inicio DATE DEFAULT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'borrador',
    pct_indirectos DECIMAL(6,4) NOT NULL,
    pct_utilidad DECIMAL(6,4) NOT NULL,
    pct_herramienta DECIMAL(6,4) NOT NULL,
    pct_iva DECIMAL(6,4) NOT NULL,
    pct_anticipo DECIMAL(6,4) NOT NULL,
    pct_retencion DECIMAL(6,4) NOT NULL DEFAULT 0,  -- fondo de garantía retenido en estimaciones
    num_estimaciones INT NOT NULL DEFAULT 2,
    alcances TEXT,
    exclusiones TEXT,
    notas TEXT,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,      -- se recalcula en cada cambio
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    usuario_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    clave VARCHAR(20) NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Claves como texto: "1.10" y "1.1" son conceptos distintos (en Excel no lo eran)
CREATE TABLE IF NOT EXISTS conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    partida_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    clave VARCHAR(20) NOT NULL,
    descripcion TEXT NOT NULL,
    unidad VARCHAR(20) NOT NULL,
    cantidad DECIMAL(14,4) NOT NULL DEFAULT 0,      -- se ignora si usa_volumenes = 1
    usa_volumenes TINYINT(1) NOT NULL DEFAULT 0,
    rendimiento DECIMAL(12,4) NOT NULL DEFAULT 1,   -- unidades por jornada de cuadrilla
    fecha_inicio DATE DEFAULT NULL,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (partida_id) REFERENCES partidas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja ANALISIS: insumos por concepto (consumo por unidad del concepto)
CREATE TABLE IF NOT EXISTS concepto_insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto_id INT NOT NULL,
    insumo_id INT NOT NULL,
    cantidad DECIMAL(14,6) NOT NULL DEFAULT 0,
    por_rendimiento TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = cantidad = 1 / rendimiento (mano de obra)
    precio DECIMAL(12,2) NOT NULL,                  -- precio congelado al agregarlo
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE,
    FOREIGN KEY (insumo_id) REFERENCES insumos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja VOLUMENES: números generadores
CREATE TABLE IF NOT EXISTS volumenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concepto_id INT NOT NULL,
    localizacion VARCHAR(150) DEFAULT NULL,
    largo DECIMAL(12,4) DEFAULT NULL,
    ancho DECIMAL(12,4) DEFAULT NULL,
    alto DECIMAL(12,4) DEFAULT NULL,
    piezas DECIMAL(12,4) DEFAULT NULL,
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================== Fase 2 =====================

CREATE TABLE IF NOT EXISTS estimaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    numero INT NOT NULL,
    fecha DATE NOT NULL,
    periodo_inicio DATE DEFAULT NULL,
    periodo_fin DATE DEFAULT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'borrador',
    notas TEXT,
    -- Totales calculados (se recalculan en cadena al guardar cualquier estimación de la obra)
    importe DECIMAL(14,2) NOT NULL DEFAULT 0,
    amortizacion DECIMAL(14,2) NOT NULL DEFAULT 0,
    iva DECIMAL(14,2) NOT NULL DEFAULT 0,
    retencion DECIMAL(14,2) NOT NULL DEFAULT 0,
    neto DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estimacion (cotizacion_id, numero),
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS estimacion_conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estimacion_id INT NOT NULL,
    concepto_id INT NOT NULL,
    cantidad DECIMAL(14,4) NOT NULL DEFAULT 0,
    pu DECIMAL(12,2) NOT NULL,                      -- P.U. contratado al momento de estimar
    UNIQUE KEY uq_est_concepto (estimacion_id, concepto_id),
    FOREIGN KEY (estimacion_id) REFERENCES estimaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja CONTROL: ingresos y egresos de la obra
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('ingreso','egreso') NOT NULL,
    categoria VARCHAR(20) NOT NULL,                 -- ingreso: ANTICIPO/ESTIMACION/PRESTAMO/OTRO · egreso: MAT/MO/EQ/SUBCONTRATO/INDIRECTO/OTRO/UTILIDAD/PRESTAMO
    partida_id INT DEFAULT NULL,
    concepto_id INT DEFAULT NULL,                   -- columna CATALOGO del Excel: gasto ligado a un concepto
    estimacion_id INT DEFAULT NULL,
    descripcion VARCHAR(255) NOT NULL,
    proveedor VARCHAR(200) DEFAULT NULL,
    rfc VARCHAR(13) DEFAULT NULL,
    forma_pago VARCHAR(30) DEFAULT NULL,
    referencia VARCHAR(60) DEFAULT NULL,
    monto DECIMAL(14,2) NOT NULL,
    comprobante VARCHAR(255) DEFAULT NULL,          -- foto o PDF del ticket/factura (app/storage/uploads)
    uuid CHAR(36) DEFAULT NULL,                     -- la app lo manda para no duplicar al reintentar sin señal
    usuario_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mov_uuid (uuid),
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (partida_id) REFERENCES partidas(id) ON DELETE SET NULL,
    CONSTRAINT fk_mov_concepto FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE SET NULL,
    FOREIGN KEY (estimacion_id) REFERENCES estimaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================== Fase 3 =====================

-- Sesiones de la app móvil (token Bearer; se guarda solo el SHA-256 del token)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    dispositivo VARCHAR(150) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso DATETIME DEFAULT NULL,
    expira DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hoja AVANCE: cantidad ejecutada por concepto y día
CREATE TABLE IF NOT EXISTS avances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    concepto_id INT NOT NULL,
    fecha DATE NOT NULL,
    cantidad DECIMAL(14,4) NOT NULL,
    notas VARCHAR(255) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    uuid CHAR(36) DEFAULT NULL UNIQUE,
    usuario_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_avance_fecha (cotizacion_id, fecha),
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (concepto_id) REFERENCES conceptos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===================== Migraciones =====================

-- Cambios a la base que el sistema aplica solo (app/database/migraciones, ver Services/Migraciones.php).
CREATE TABLE IF NOT EXISTS migraciones (
    archivo VARCHAR(150) PRIMARY KEY,
    aplicada DATETIME DEFAULT NULL,                 -- NULL = falló; se reintenta
    intento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    error TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Este esquema ya incluye estas migraciones (y insumos_iniciales.sql los insumos de la 004).
INSERT IGNORE INTO migraciones (archivo, aplicada) VALUES
('002_fase2_estimaciones_control.sql', NOW()),
('003_fase3_app_movil_avance.sql', NOW()),
('004_fase4_insumos_2024.sql', NOW());
