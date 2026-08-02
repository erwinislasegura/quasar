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
    if (($_POST['email'] ?? '') === 'admin@quasar.local' && ($_POST['password'] ?? '') === (getenv('ADMIN_PASSWORD') ?: 'quasar123')) {
        session_regenerate_id(true); $_SESSION['user'] = ['name' => 'Administrador local', 'role' => 'Administrador']; header('Location: /'); return;
    }
    $_SESSION['flashes']['error'] = 'Las credenciales no son correctas.'; header('Location: /login'); return;
}
if ($path === '/logout') { $_SESSION = []; session_destroy(); header('Location: /login'); return; }

// Set REQUIRE_AUTH=1 in production; keeping it optional permits visual review immediately.
if (getenv('REQUIRE_AUTH') === '1' && empty($_SESSION['user'])) { header('Location: /login'); return; }

if ($path === '/') { (new App\Controllers\DashboardController())->index(); return; }
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
