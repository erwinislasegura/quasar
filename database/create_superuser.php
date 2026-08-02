<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$connect = require dirname(__DIR__) . '/config/database.php';
$pdo = $connect();
if (!$pdo instanceof PDO) {
    fwrite(STDERR, "Configure DB_HOST y las demás variables de base de datos.\n");
    exit(1);
}

$email = $argv[1] ?? 'admin@quasar.local';
$password = $argv[2] ?? getenv('ADMIN_PASSWORD') ?: 'quasar123';
$name = $argv[3] ?? 'Superusuario';

$pdo->beginTransaction();
try {
    $pdo->exec("INSERT INTO roles (nombre) VALUES ('Superadministrador') ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
    $roleId = (int) $pdo->query("SELECT id FROM roles WHERE nombre = 'Superadministrador'")->fetchColumn();
    $statement = $pdo->prepare('INSERT INTO usuarios (rol_id, nombre, email, password_hash, activo) VALUES (:rol, :nombre, :email, :hash, 1) ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id), nombre = VALUES(nombre), password_hash = VALUES(password_hash), activo = 1');
    $statement->execute(['rol' => $roleId, 'nombre' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
    $pdo->commit();
    echo "Superusuario creado: {$email}\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
