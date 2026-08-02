<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['email:', 'new-email::', 'name::', 'password::', 'role::', 'enable', 'disable', 'help']);
if (isset($options['help']) || !isset($options['email'])) {
    echo <<<'HELP'
Actualiza un usuario existente sin borrar ni recrear la base de datos.

Uso:
  php database/update_user.php --email=actual@correo.cl [opciones]

Opciones:
  --new-email=nuevo@correo.cl   Cambia el correo de acceso
  --name="Nuevo nombre"         Cambia el nombre visible
  --password="Clave segura"     Genera y guarda un nuevo password_hash
  --role=Superadministrador     Asigna un rol existente o lo crea
  --enable                      Activa el usuario
  --disable                     Desactiva el usuario
  --help                        Muestra esta ayuda

HELP;
    exit(isset($options['help']) ? 0 : 1);
}

if (isset($options['enable'], $options['disable'])) {
    fwrite(STDERR, "Use solamente --enable o --disable, no ambos.\n");
    exit(1);
}

$connect = require dirname(__DIR__) . '/config/database.php';
$pdo = $connect();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Configure DB_HOST, DB_DATABASE, DB_USERNAME y DB_PASSWORD.\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $find = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email FOR UPDATE');
    $find->execute(['email' => $options['email']]);
    $userId = $find->fetchColumn();
    if ($userId === false) {
        throw new RuntimeException('No existe un usuario con el correo indicado. Use create_superuser.php para crearlo.');
    }

    $changes = [];
    $parameters = ['id' => (int) $userId];
    if (isset($options['new-email']) && $options['new-email'] !== false) {
        if (!filter_var($options['new-email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('El nuevo correo no es válido.');
        $changes[] = 'email = :new_email'; $parameters['new_email'] = $options['new-email'];
    }
    if (isset($options['name']) && trim((string) $options['name']) !== '') {
        $changes[] = 'nombre = :nombre'; $parameters['nombre'] = trim((string) $options['name']);
    }
    if (isset($options['password']) && $options['password'] !== false) {
        if (strlen((string) $options['password']) < 10) throw new InvalidArgumentException('La contraseña debe tener al menos 10 caracteres.');
        $changes[] = 'password_hash = :password_hash'; $parameters['password_hash'] = password_hash((string) $options['password'], PASSWORD_DEFAULT);
    }
    if (isset($options['role']) && trim((string) $options['role']) !== '') {
        $role = trim((string) $options['role']);
        $createRole = $pdo->prepare('INSERT INTO roles (nombre) VALUES (:nombre) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)');
        $createRole->execute(['nombre' => $role]);
        $roleId = $pdo->prepare('SELECT id FROM roles WHERE nombre = :nombre');
        $roleId->execute(['nombre' => $role]);
        $changes[] = 'rol_id = :rol_id'; $parameters['rol_id'] = (int) $roleId->fetchColumn();
    }
    if (isset($options['enable']) || isset($options['disable'])) {
        $changes[] = 'activo = :activo'; $parameters['activo'] = isset($options['enable']) ? 1 : 0;
    }
    if ($changes === []) throw new InvalidArgumentException('No se indicó ningún cambio. Use --help para ver las opciones.');

    $update = $pdo->prepare('UPDATE usuarios SET ' . implode(', ', $changes) . ' WHERE id = :id');
    $update->execute($parameters);
    $pdo->commit();
    echo "Usuario actualizado correctamente.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'No se pudo actualizar: ' . $error->getMessage() . "\n");
    exit(1);
}
