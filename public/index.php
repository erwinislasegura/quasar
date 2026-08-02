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
$basePath = rtrim(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '')), '/.');
if (str_ends_with($basePath, '/public')) $basePath = substr($basePath, 0, -7);
if ($basePath !== '' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) $path = substr($path, strlen($basePath)) ?: '/';
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
        if ($pdo instanceof PDO) {
            $audit = $pdo->prepare('INSERT INTO auditoria (usuario_id, accion, contexto) SELECT id, :accion, :contexto FROM usuarios WHERE email = :email');
            $audit->execute(['accion' => 'Inicio de sesión', 'contexto' => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? null]), 'email' => $user['email']]);
        }
        session_regenerate_id(true); $_SESSION['user'] = ['name' => $user['nombre'], 'email' => $user['email'], 'role' => $user['rol']]; header('Location: ' . url()); return;
    }
    $_SESSION['flashes']['error'] = 'Las credenciales no son correctas.'; header('Location: ' . url('login')); return;
}
if ($path === '/logout') { $_SESSION = []; session_destroy(); header('Location: ' . url('login')); return; }

if ($path === '/api/measurements' && $method === 'POST') {
    if (!hash_equals(getenv('AGENT_API_KEY') ?: 'change-this-agent-key', $_SERVER['HTTP_X_API_KEY'] ?? '')) { http_response_code(401); echo json_encode(['error' => 'No autorizado']); return; }
    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    $line = trim((string) ($payload['line'] ?? ''));
    if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})-(\d{2}:\d{2}:\d{2});Tiempo;(-?\d+[,.]\d+);Razon;(-?\d+[,.]\d+);Conductividad;(-?\d+[,.]\d+)$/', $line, $fields)) { http_response_code(422); echo json_encode(['error' => 'Línea inválida']); return; }

    $connect = require dirname(__DIR__) . '/config/database.php';
    $pdo = $connect();
    if ($pdo instanceof PDO) {
        $identifier = trim((string) ($payload['equipmentId'] ?? 'windows-agent-01'));
        $equipmentName = trim((string) ($payload['equipmentName'] ?? 'Analizador Windows 01'));
        $pdo->beginTransaction();
        try {
            $equipment = $pdo->prepare('INSERT INTO equipos (nombre, identificador, conectado, last_seen_at) VALUES (:nombre, :identificador, 1, NOW()) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nombre = VALUES(nombre), conectado = 1, last_seen_at = NOW()');
            $equipment->execute(['nombre' => $equipmentName, 'identificador' => $identifier]);
            $equipmentId = (int) $pdo->lastInsertId();
            $checksum = hash('sha256', $identifier . '|' . $line);
            $file = $pdo->prepare("INSERT INTO archivos (equipo_id, nombre, checksum, estado) VALUES (:equipo, 'Analisis.txt', :checksum, 'Procesado') ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
            $file->execute(['equipo' => $equipmentId, 'checksum' => $checksum]);
            $fileId = (int) $pdo->lastInsertId();
            $exists = $pdo->prepare('SELECT 1 FROM mediciones WHERE archivo_id = :archivo LIMIT 1');
            $exists->execute(['archivo' => $fileId]);
            if (!$exists->fetchColumn()) {
                $measurement = $pdo->prepare("INSERT INTO mediciones (archivo_id, equipo_id, measured_at, tsf, razon_oa, conductividad, estado, archivo, equipo) VALUES (:archivo_id, :equipo_id, :fecha, :tsf, :razon, :conductividad, :estado, 'Analisis.txt', :equipo)");
                $measurement->execute([
                    'archivo_id' => $fileId, 'equipo_id' => $equipmentId,
                    'fecha' => "{$fields[3]}-{$fields[2]}-{$fields[1]} {$fields[4]}",
                    'tsf' => str_replace(',', '.', $fields[5]), 'razon' => str_replace(',', '.', $fields[6]),
                    'conductividad' => str_replace(',', '.', $fields[7]),
                    'estado' => (float) str_replace(',', '.', $fields[7]) < 0 ? 'Revisar' : 'Válido', 'equipo' => $equipmentName,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(500); echo json_encode(['error' => 'No fue posible guardar la medición']); return;
        }
    } else {
        $target = dirname(__DIR__) . '/Analisis.txt';
        $current = is_file($target) ? file_get_contents($target) : '';
        if (!str_contains((string) $current, $line)) file_put_contents($target, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    header('Content-Type: application/json'); http_response_code(201); echo json_encode(['status' => 'created', 'storage' => $pdo instanceof PDO ? 'mysql' : 'txt']); return;
}

if ($path === '/api/agent/status' && $method === 'GET') {
    header('Content-Type: application/json');
    if (!hash_equals(getenv('AGENT_API_KEY') ?: 'change-this-agent-key', $_SERVER['HTTP_X_API_KEY'] ?? '')) {
        http_response_code(401); echo json_encode(['error' => 'No autorizado']); return;
    }
    echo json_encode(['status' => 'ok', 'serverTime' => gmdate('c')]); return;
}

// El panel siempre requiere una sesión iniciada.
if (empty($_SESSION['user'])) { header('Location: ' . url('login')); return; }

if ($path === '/') { define('QUASAR_ROUTED', true); require dirname(__DIR__) . '/index.php'; return; }
if ($path === '/windows-reader') {
    view('windows-agent/index', ['title' => 'Lector Windows', 'subtitle' => 'Lectura local desde Microsoft Edge o Google Chrome', 'active' => 'windows-reader']);
    return;
}
// Preserve bookmarks created before the browser reader got its definitive name.
if ($path === '/windows-agent') { header('Location: ' . url('windows-reader')); return; }
if ($path === '/windows-agent/download') {
    $downloads = [
        'agent' => ['QuasarAgent.ps1', 'text/plain; charset=utf-8'],
        'config' => ['config.example.json', 'application/json'],
    ];
    $download = $downloads[$_GET['file'] ?? ''] ?? null;
    if ($download === null) { http_response_code(404); exit('Archivo no encontrado'); }
    [$filename, $contentType] = $download;
    $file = dirname(__DIR__) . '/windows-agent/' . $filename;
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return;
}
if ($path === '/windows-agent/installer') {
    $template = file_get_contents(dirname(__DIR__) . '/windows-agent/Install-QuasarAgent.ps1');
    $agent = file_get_contents(dirname(__DIR__) . '/windows-agent/QuasarAgent.ps1');
    if ($template === false || $agent === false) { http_response_code(500); exit('No fue posible generar el instalador'); }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $serverUrl = $scheme . '://' . $host . rtrim($basePath, '/');
    $installer = str_replace(['__QUASAR_SERVER_URL__', '__QUASAR_AGENT_BASE64__'], [$serverUrl, base64_encode($agent)], $template);
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="Instalar-Quasar.ps1"');
    header('Content-Length: ' . strlen($installer));
    echo $installer;
    return;
}
if ($path === '/configuracion') {
    $connect = require dirname(__DIR__) . '/config/database.php';
    view('configuracion/index', [
        'title' => 'Configuración', 'subtitle' => 'Conexiones y servicios del sistema', 'active' => 'configuracion',
        'databaseConnected' => $connect() instanceof PDO,
        'agentKeyConfigured' => (getenv('AGENT_API_KEY') ?: '') !== '',
    ]);
    return;
}
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
