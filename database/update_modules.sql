-- Actualiza una base Quasar EN USO con los módulos visibles del dashboard.
-- Es idempotente: puede ejecutarse nuevamente sin duplicar registros.
USE quasar;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS modulos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(80) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    ruta VARCHAR(190) NOT NULL,
    seccion VARCHAR(80) NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS errores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(120) NOT NULL,
    detalle TEXT NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Conserva el registro y sus relaciones al actualizar instalaciones anteriores.
UPDATE modulos
SET clave = 'windows-reader', nombre = 'Lector Windows', ruta = '/windows-reader'
WHERE clave = 'windows-agent';

INSERT INTO modulos (clave, nombre, ruta, seccion, orden, activo) VALUES
('dashboard', 'Dashboard', '/', 'Principal', 10, 1),
('mediciones', 'Mediciones', '/mediciones', 'Gestión', 20, 1),
('archivos', 'Archivos', '/archivos', 'Gestión', 30, 1),
('equipos', 'Equipos', '/equipos', 'Gestión', 40, 1),
('windows-reader', 'Lector Windows', '/windows-reader', 'Gestión', 50, 1),
('usuarios', 'Usuarios', '/usuarios', 'Administración', 60, 1),
('roles', 'Roles', '/roles', 'Administración', 70, 1),
('permisos', 'Permisos', '/permisos', 'Administración', 80, 1),
('auditoria', 'Auditoría', '/auditoria', 'Administración', 90, 1),
('errores', 'Errores', '/errores', 'Sistema', 100, 1),
('configuracion', 'Configuración', '/configuracion', 'Sistema', 110, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), ruta = VALUES(ruta), seccion = VALUES(seccion), orden = VALUES(orden), activo = VALUES(activo);

INSERT INTO permisos (codigo, descripcion)
SELECT CONCAT(clave, '.view'), CONCAT('Visualizar módulo ', nombre)
FROM modulos
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
JOIN permisos p ON p.codigo LIKE '%.view'
WHERE r.nombre = 'Superadministrador'
ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id);

COMMIT;

SELECT clave, nombre, ruta, seccion, orden, activo FROM modulos ORDER BY orden;
