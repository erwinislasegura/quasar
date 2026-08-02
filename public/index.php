<?php

declare(strict_types=1);

$publicFile = __DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (PHP_SAPI === 'cli-server' && is_file($publicFile)) {
    return false;
}

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
require dirname(__DIR__) . '/app/Core/helpers.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) require $file;
    }
});

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($path === '/login' && $method === 'GET') { view('auth/login', ['title' => 'Acceso'], 'auth'); return; }
if ($path === '/login' && $method === 'POST') {
    if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Sesión expirada'); }
    $connect = require dirname(__DIR__) . '/config/database.php';
    $pdo = $connect();
    $user = null;
    if ($pdo instanceof PDO) {
        $statement = $pdo->prepare('SELECT u.nombre, u.email, u.password_hash, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.email = :email AND u.activo = 1 LIMIT 1');
        $statement->execute(['email' => $_POST['email'] ?? '']);
        $user = $statement->fetch() ?: null;
    } elseif (($_POST['email'] ?? '') === 'admin@quasar.local') {
        $user = ['nombre' => 'Superusuario', 'email' => 'admin@quasar.local', 'password_hash' => password_hash(getenv('ADMIN_PASSWORD') ?: 'quasar123', PASSWORD_DEFAULT), 'rol' => 'Superadministrador'];
    }
    if ($user !== null && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
        session_regenerate_id(true); $_SESSION['user'] = ['name' => $user['nombre'], 'email' => $user['email'], 'role' => $user['rol']]; header('Location: /'); return;
    }
    $_SESSION['flashes']['error'] = 'Las credenciales no son correctas.'; header('Location: /login'); return;
}
if ($path === '/logout') { $_SESSION = []; session_destroy(); header('Location: /login'); return; }

if ($path === '/api/measurements' && $method === 'POST') {
    if (!hash_equals(getenv('AGENT_API_KEY') ?: 'change-this-agent-key', $_SERVER['HTTP_X_API_KEY'] ?? '')) { http_response_code(401); echo json_encode(['error' => 'No autorizado']); return; }
    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    $line = trim((string) ($payload['line'] ?? ''));
    if (!preg_match('/^\d{2}-\d{2}-\d{4}-\d{2}:\d{2}:\d{2};Tiempo;-?\d+[,.]\d+;Razon;-?\d+[,.]\d+;Conductividad;-?\d+[,.]\d+$/', $line)) { http_response_code(422); echo json_encode(['error' => 'Línea inválida']); return; }
    file_put_contents(dirname(__DIR__) . '/Analisis.txt', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    header('Content-Type: application/json'); http_response_code(201); echo json_encode(['status' => 'created']); return;
}

// El panel siempre requiere una sesión iniciada.
if (empty($_SESSION['user'])) { header('Location: /login'); return; }

if ($path === '/') { require dirname(__DIR__) . '/index.php'; return; }
$modules = [
    'mediciones' => ['key'=>'mediciones','title'=>'Mediciones','subtitle'=>'Consulta de variables procesadas'],
    'archivos' => ['key'=>'archivos','title'=>'Archivos','subtitle'=>'Archivos recibidos y procesados'],
    'equipos' => ['key'=>'equipos','title'=>'Equipos','subtitle'=>'Estado de los agentes locales'],
    'usuarios' => ['key'=>'usuarios','title'=>'Usuarios','subtitle'=>'Administración de accesos'],
    'roles' => ['key'=>'roles','title'=>'Roles','subtitle'=>'Perfiles de acceso del sistema'],
    'permisos' => ['key'=>'permisos','title'=>'Permisos','subtitle'=>'Acciones disponibles por rol'],
    'errores' => ['key'=>'errores','title'=>'Errores','subtitle'=>'Incidencias de procesamiento'],
    'configuracion' => ['key'=>'configuracion','title'=>'Configuración','subtitle'=>'Parámetros generales del sistema'],
    'auditoria' => ['key'=>'auditoria','title'=>'Auditoría','subtitle'=>'Trazabilidad de acciones'],
];
$key = ltrim($path, '/');
if (isset($modules[$key])) { (new App\Controllers\ModuleController())->index($modules[$key]); return; }
http_response_code(404); view('errors/404', ['title'=>'Página no encontrada','subtitle'=>'Error 404','active'=>'']);
