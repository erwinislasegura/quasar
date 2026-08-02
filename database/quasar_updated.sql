-- Base de datos completa y actualizada de Quasar.
-- Use este archivo solamente en instalaciones nuevas.

CREATE DATABASE IF NOT EXISTS quasar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quasar;

CREATE TABLE IF NOT EXISTS roles (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(80) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS permisos (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, codigo VARCHAR(120) NOT NULL UNIQUE, descripcion VARCHAR(255) NOT NULL);
CREATE TABLE IF NOT EXISTS modulos (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, clave VARCHAR(80) NOT NULL UNIQUE, nombre VARCHAR(120) NOT NULL, ruta VARCHAR(190) NOT NULL, seccion VARCHAR(80) NOT NULL, orden SMALLINT UNSIGNED NOT NULL DEFAULT 0, activo BOOLEAN NOT NULL DEFAULT TRUE);
CREATE TABLE IF NOT EXISTS rol_permiso (rol_id BIGINT UNSIGNED NOT NULL, permiso_id BIGINT UNSIGNED NOT NULL, PRIMARY KEY (rol_id, permiso_id), FOREIGN KEY (rol_id) REFERENCES roles(id), FOREIGN KEY (permiso_id) REFERENCES permisos(id));
CREATE TABLE IF NOT EXISTS usuarios (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, rol_id BIGINT UNSIGNED NOT NULL, nombre VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, activo BOOLEAN NOT NULL DEFAULT TRUE, FOREIGN KEY (rol_id) REFERENCES roles(id));
CREATE TABLE IF NOT EXISTS equipos (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL, identificador VARCHAR(120) NOT NULL UNIQUE, conectado BOOLEAN NOT NULL DEFAULT FALSE, last_seen_at DATETIME NULL);
CREATE TABLE IF NOT EXISTS archivos (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, equipo_id BIGINT UNSIGNED NOT NULL, nombre VARCHAR(255) NOT NULL, checksum CHAR(64) NOT NULL UNIQUE, estado VARCHAR(30) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (equipo_id) REFERENCES equipos(id));
CREATE TABLE IF NOT EXISTS mediciones (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, archivo_id BIGINT UNSIGNED NOT NULL, equipo_id BIGINT UNSIGNED NOT NULL, measured_at DATETIME NOT NULL, tsf DECIMAL(16,6) NOT NULL, razon_oa DECIMAL(16,6) NOT NULL, conductividad DECIMAL(16,6) NOT NULL, estado VARCHAR(30) NOT NULL, archivo VARCHAR(255) NOT NULL, equipo VARCHAR(120) NOT NULL, INDEX (measured_at), FOREIGN KEY (archivo_id) REFERENCES archivos(id), FOREIGN KEY (equipo_id) REFERENCES equipos(id));
CREATE TABLE IF NOT EXISTS auditoria (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id BIGINT UNSIGNED NULL, accion VARCHAR(160) NOT NULL, contexto JSON NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (usuario_id) REFERENCES usuarios(id));
CREATE TABLE IF NOT EXISTS errores (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, origen VARCHAR(120) NOT NULL, detalle TEXT NOT NULL, estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

INSERT INTO roles (nombre) VALUES ('Superadministrador') ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
INSERT INTO usuarios (rol_id, nombre, email, password_hash, activo)
SELECT id, 'Superusuario', 'admin@quasar.local', '$2y$12$Mgmd.52LWU.MtqLAHhGOu.GwZ6p9nRHFdZ8f4lzG1ivPMd1DNFT9a', 1 FROM roles WHERE nombre = 'Superadministrador'
ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id), nombre = VALUES(nombre), password_hash = VALUES(password_hash), activo = 1;

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
SELECT CONCAT(clave, '.view'), CONCAT('Visualizar módulo ', nombre) FROM modulos
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.codigo LIKE '%.view' WHERE r.nombre = 'Superadministrador'
ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id);
