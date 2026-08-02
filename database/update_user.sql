-- Actualización segura del superusuario en una base Quasar existente.
-- No elimina tablas ni mediciones. Cambie los valores antes de ejecutar.
-- Contraseña incluida en el hash: quasar123 (debe cambiarse después del acceso).

SET @current_email = 'admin@quasar.local';
SET @new_email = 'admin@quasar.local';
SET @new_name = 'Superusuario';
SET @new_role = 'Superadministrador';
SET @new_password_hash = '$2y$12$Mgmd.52LWU.MtqLAHhGOu.GwZ6p9nRHFdZ8f4lzG1ivPMd1DNFT9a';

START TRANSACTION;

INSERT INTO roles (nombre)
VALUES (@new_role)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

UPDATE usuarios
SET rol_id = (SELECT id FROM roles WHERE nombre = @new_role LIMIT 1),
    nombre = @new_name,
    email = @new_email,
    password_hash = @new_password_hash,
    activo = 1
WHERE email = @current_email;

COMMIT;

SELECT id, nombre, email, rol_id, activo
FROM usuarios
WHERE email = @new_email;
