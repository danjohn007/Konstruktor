-- Konstruktor — Asignación de obras a ingenieros (residentes). Cada ingeniero ve solo sus obras.
-- Para que nadie deje de ver lo que ya veía, las obras aceptadas que existen quedan asignadas a
-- todos los ingenieros actuales; después se ajusta en cada cotización (botón "Asignar").
-- No va marcada como aplicada en schema.sql: así también asigna las obras de los datos de prueba (006).
CREATE TABLE IF NOT EXISTS obra_ingenieros (
    cotizacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cotizacion_id, usuario_id),
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO obra_ingenieros (cotizacion_id, usuario_id)
SELECT c.id, u.id FROM cotizaciones c
JOIN usuarios u JOIN roles r ON r.id = u.rol_id AND r.nombre = 'Ingeniero'
WHERE c.estado = 'aceptada';
