-- Konstruktor — Fase 4: datos del Excel 2024.
-- Para bases creadas antes de Fase 4 (se puede correr más de una vez). Las instalaciones nuevas
-- ya traen estos insumos en insumos_iniciales.sql.
SET NAMES utf8mb4;

-- Materiales que se usaron en obras de 2024 y no estaban en el catálogo (precio de 2024).
-- Si ya existe un insumo con la misma clave, se respeta el que está.
INSERT IGNORE INTO insumos (clave, descripcion, unidad, tipo, categoria, precio_captura, mas_iva, precio) VALUES
('TIN-1100', 'TINACO 1100LTS', 'PZA', 'MAT', 'INSTALACIONES HIDRAULICAS Y SANITARIAS', 3000.00, 0, 3000.00),
('TIN-ACC', 'ACCESORIOS TINACO', 'JGO', 'MAT', 'INSTALACIONES HIDRAULICAS Y SANITARIAS', 1000.00, 0, 1000.00),
('PUE-PRE-90', 'PUERTA PRINCIPAL PREFABRICADA 90CM', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 2600.00, 0, 2600.00),
('VEN-ALU-170', 'VENTANA DE ALUMINIO 170x120', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 4130.00, 0, 4130.00),
('VEN-ALU-090', 'VENTANA DE ALUMINIO 0.90x120', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 1960.00, 0, 1960.00),
('VEN-ALU-070', 'VENTANA DE ALUMINIO 0.70x120', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 1750.00, 0, 1750.00),
('VEN-ALU-60-40', 'VENTANA DE ALUMINIO 0.60x40', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 910.00, 0, 910.00),
('HERR-IPR-8', 'VIGA IPR 8x4"', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 8000.00, 0, 8000.00),
('HERR-PTR-2', 'PTR 2" 6M', 'TRAMO', 'MAT', 'HERRERIA Y ALUMINIO', 900.00, 0, 900.00),
('LAM-305-122', 'LAMINA LISA 3.05X1.22M', 'PZA', 'MAT', 'HERRERIA Y ALUMINIO', 1850.00, 0, 1850.00);
