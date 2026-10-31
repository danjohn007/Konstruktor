-- Konstruktor — Fase 3: app móvil, avance diario y comprobantes.
-- Para bases creadas antes de Fase 3. Las instalaciones nuevas ya lo traen en schema.sql.
SET NAMES utf8mb4;

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

-- Hoja CONTROL: cada gasto puede ligarse a un concepto del catálogo (columna CATALOGO del Excel)
-- y llevar la foto del ticket o factura. uuid evita duplicados cuando la app reintenta sin señal.
ALTER TABLE movimientos ADD COLUMN IF NOT EXISTS concepto_id INT DEFAULT NULL AFTER partida_id;
ALTER TABLE movimientos ADD COLUMN IF NOT EXISTS comprobante VARCHAR(255) DEFAULT NULL;
ALTER TABLE movimientos ADD COLUMN IF NOT EXISTS uuid CHAR(36) DEFAULT NULL;
ALTER TABLE movimientos ADD UNIQUE INDEX IF NOT EXISTS uq_mov_uuid (uuid);
ALTER TABLE movimientos ADD CONSTRAINT fk_mov_concepto FOREIGN KEY IF NOT EXISTS (concepto_id) REFERENCES conceptos(id) ON DELETE SET NULL;

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
