-- Konstruktor — Fase 2: estimaciones y control de obra.
-- Para bases creadas con el schema.sql de Fase 1. Las instalaciones nuevas ya lo traen en schema.sql.
SET NAMES utf8mb4;

ALTER TABLE cotizaciones ADD COLUMN IF NOT EXISTS pct_retencion DECIMAL(6,4) NOT NULL DEFAULT 0 AFTER pct_anticipo;

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
    categoria VARCHAR(20) NOT NULL,                 -- ingreso: ANTICIPO/ESTIMACION/OTRO · egreso: MAT/MO/EQ/SUBCONTRATO/INDIRECTO/OTRO
    partida_id INT DEFAULT NULL,
    estimacion_id INT DEFAULT NULL,
    descripcion VARCHAR(255) NOT NULL,
    proveedor VARCHAR(200) DEFAULT NULL,
    rfc VARCHAR(13) DEFAULT NULL,
    forma_pago VARCHAR(30) DEFAULT NULL,
    referencia VARCHAR(60) DEFAULT NULL,
    monto DECIMAL(14,2) NOT NULL,
    usuario_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (partida_id) REFERENCES partidas(id) ON DELETE SET NULL,
    FOREIGN KEY (estimacion_id) REFERENCES estimaciones(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
