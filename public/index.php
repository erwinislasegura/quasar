<?php

declare(strict_types=1);

if (defined('QUASAR_FRONT_CONTROLLER_LOADED')) {
    return;
}
define('QUASAR_FRONT_CONTROLLER_LOADED', true);

$publicFile = __DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (PHP_SAPI === 'cli-server' && is_file($publicFile)) {
    return false;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}
require_once dirname(__DIR__) . '/app/Core/helpers.php';

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

$ensureAdminSchema = static function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(80) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS permisos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(120) NOT NULL UNIQUE,
        descripcion VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        rol_id BIGINT UNSIGNED NOT NULL,
        nombre VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        activo BOOLEAN NOT NULL DEFAULT TRUE,
        INDEX idx_usuarios_rol (rol_id),
        CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS rol_permiso (
        rol_id BIGINT UNSIGNED NOT NULL,
        permiso_id BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (rol_id, permiso_id),
        CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES roles(id),
        CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS auditoria (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        usuario_id BIGINT UNSIGNED NULL,
        accion VARCHAR(160) NOT NULL,
        contexto JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_auditoria_usuario (usuario_id),
        CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS errores (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        origen VARCHAR(120) NOT NULL,
        detalle TEXT NOT NULL,
        estado VARCHAR(30) NOT NULL DEFAULT 'Pendiente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("INSERT INTO roles (nombre) VALUES ('Superadministrador')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
    $pdo->exec("INSERT INTO roles (nombre) VALUES ('Lector Windows'),('Administrador limitado')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
    $permissionDefinitions = [
        'dashboard.view'=>'Ver panel principal','measurements.view'=>'Ver mediciones','files.view'=>'Ver archivos procesados',
        'equipment.manage'=>'Administrar equipos','reader.use'=>'Usar Lector Windows','users.manage'=>'Administrar usuarios',
        'roles.manage'=>'Administrar roles y permisos','errors.view'=>'Ver errores','audit.view'=>'Ver auditoría',
    ];
    $permissionInsert=$pdo->prepare('INSERT INTO permisos(codigo,descripcion) VALUES(:codigo,:descripcion) ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion)');
    foreach($permissionDefinitions as $code=>$description)$permissionInsert->execute(['codigo'=>$code,'descripcion'=>$description]);
    $pdo->exec("DELETE rp FROM rol_permiso rp JOIN permisos p ON p.id=rp.permiso_id WHERE p.codigo='config.view'");
    $pdo->exec("DELETE FROM permisos WHERE codigo='config.view'");
    $pdo->exec("INSERT IGNORE INTO rol_permiso(rol_id,permiso_id) SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.nombre='Superadministrador'");
    $pdo->exec("INSERT IGNORE INTO rol_permiso(rol_id,permiso_id) SELECT r.id,p.id FROM roles r JOIN permisos p ON p.codigo='reader.use' WHERE r.nombre='Lector Windows'");
    $pdo->exec("INSERT IGNORE INTO rol_permiso(rol_id,permiso_id) SELECT r.id,p.id FROM roles r CROSS JOIN permisos p WHERE r.nombre='Administrador limitado' AND p.codigo NOT IN('users.manage','roles.manage')");
    $pdo->exec("INSERT INTO usuarios (rol_id, nombre, email, password_hash, activo)
        SELECT id, 'Superusuario', 'admin@quasar.local', '$2y$12$Mgmd.52LWU.MtqLAHhGOu.GwZ6p9nRHFdZ8f4lzG1ivPMd1DNFT9a', 1
        FROM roles WHERE nombre = 'Superadministrador'
        ON DUPLICATE KEY UPDATE rol_id = VALUES(rol_id), nombre = VALUES(nombre), activo = 1");
    $systemUsers = [
        ['Lector Windows','Lector Windows 1','lector1@quasartech.cl','$2y$12$ZGLNg0DPcYmP6d8WC5Z/YuuTaoJiCi14/G/XO.QI8sOXEKIYsp412'],
        ['Lector Windows','Lector Windows 2','lector2@quasartech.cl','$2y$12$DRC4kDywW.IxzwuL5XeOCO8ZePkLUDmSZWWe3qv3jYkOnOr88qMtG'],
        ['Administrador limitado','Administrador Operaciones','admin.operaciones@quasartech.cl','$2y$12$l9Ks/ys8KzCj5a9YmkQDEO/FyPSHESxwKbde9/kV1v97DK5ocF3ti'],
    ];
    $seedUser = $pdo->prepare('INSERT INTO usuarios (rol_id,nombre,email,password_hash,activo) SELECT id,:nombre,:email,:password,1 FROM roles WHERE nombre=:rol ON DUPLICATE KEY UPDATE rol_id=VALUES(rol_id),nombre=VALUES(nombre)');
    foreach ($systemUsers as [$role,$name,$email,$password]) $seedUser->execute(['rol'=>$role,'nombre'=>$name,'email'=>$email,'password'=>$password]);
};

if ($path === '/login' && $method === 'GET') { view('auth/login', ['title' => 'Acceso'], 'auth'); return; }
if ($path === '/login' && $method === 'POST') {
    if (!hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Sesión expirada'); }
    $connect = require dirname(__DIR__) . '/config/database.php';
    $pdo = $connect();
    $user = null;
    if ($pdo instanceof PDO) {
        try {
            $ensureAdminSchema($pdo);
            $statement = $pdo->prepare('SELECT u.id,u.nombre,u.email,u.password_hash,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.email=:email AND u.activo=1 LIMIT 1');
            $statement->execute(['email' => $_POST['email'] ?? '']);
            $user = $statement->fetch() ?: null;
        } catch (Throwable $error) {
            record_system_error($pdo??null,'Login',$error->getMessage());
            $_SESSION['flashes']['error'] = 'No fue posible iniciar sesión. Intente nuevamente.';
            header('Location: ' . url('login')); return;
        }
    } elseif (($_POST['email'] ?? '') === 'admin@quasar.local') {
        $user = ['nombre' => 'Superusuario', 'email' => 'admin@quasar.local', 'password_hash' => password_hash(getenv('ADMIN_PASSWORD') ?: 'quasar123', PASSWORD_DEFAULT), 'rol' => 'Superadministrador'];
    }
    if ($user !== null && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
        if ($pdo instanceof PDO) {
            $audit = $pdo->prepare('INSERT INTO auditoria (usuario_id, accion, contexto) SELECT id, :accion, :contexto FROM usuarios WHERE email = :email');
            $audit->execute(['accion' => 'Inicio de sesión', 'contexto' => json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? null]), 'email' => $user['email']]);
        }
        $permissions=[];
        if($pdo instanceof PDO){$permissionQuery=$pdo->prepare('SELECT p.codigo FROM rol_permiso rp JOIN permisos p ON p.id=rp.permiso_id JOIN usuarios u ON u.rol_id=rp.rol_id WHERE u.id=:id');$permissionQuery->execute(['id'=>$user['id']]);$permissions=$permissionQuery->fetchAll(PDO::FETCH_COLUMN);}
        session_regenerate_id(true); $_SESSION['user'] = ['name'=>$user['nombre'],'email'=>$user['email'],'role'=>$user['rol'],'permissions'=>$permissions];
        header('Location: ' . ($user['rol'] === 'Lector Windows' ? url('windows-reader') : url())); return;
    }
    $_SESSION['flashes']['error'] = 'Las credenciales no son correctas.'; header('Location: ' . url('login')); return;
}
if ($path === '/logout') { $_SESSION = []; session_destroy(); header('Location: ' . url('login')); return; }

if ($path === '/api/measurements' && $method === 'POST') {
    $agentApiKey = (string) (getenv('AGENT_API_KEY') ?: '');
    $sessionAuthorized = !empty($_SESSION['user']) && can('reader.use');
    $keyAuthorized = $agentApiKey !== '' && hash_equals($agentApiKey, $_SERVER['HTTP_X_API_KEY'] ?? '');
    if (!$sessionAuthorized && !$keyAuthorized) { http_response_code(401); echo json_encode(['error' => 'No autorizado']); return; }
    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    $line = trim((string) ($payload['line'] ?? ''));
    if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})-(\d{1,2}:\d{2}:\d{2});Tiempo;(-?\d+[,.]\d+);Razon;(-?\d+[,.]\d+);Conductividad;(-?\d+[,.]\d+)$/', $line, $fields)) { http_response_code(422); echo json_encode(['error' => 'Línea inválida']); return; }
    [$hour, $minute, $second] = array_map('intval', explode(':', $fields[4]));
    if ($hour > 23 || $minute > 59 || $second > 59) { http_response_code(422); echo json_encode(['error' => 'Hora inválida']); return; }
    $normalizedTime = sprintf('%02d:%02d:%02d', $hour, $minute, $second);

    $connect = require dirname(__DIR__) . '/config/database.php';
    $pdo = $connect();
    if ($pdo instanceof PDO) {
        $identifier = trim((string) ($payload['equipmentId'] ?? 'windows-agent-01'));
        $equipmentName = trim((string) ($payload['equipmentName'] ?? 'Analizador Windows 01'));
        try {
            // El agente puede instalarse antes que el panel. Garantizamos aquí
            // el esquema mínimo requerido sin eliminar ni reemplazar datos.
            $pdo->exec("CREATE TABLE IF NOT EXISTS equipos (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                identificador VARCHAR(120) NOT NULL UNIQUE,
                conectado BOOLEAN NOT NULL DEFAULT FALSE,
                activo BOOLEAN NOT NULL DEFAULT TRUE,
                last_seen_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $activeColumn = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='equipos' AND column_name='activo' LIMIT 1")->fetchColumn();
            if (!$activeColumn) $pdo->exec('ALTER TABLE equipos ADD activo BOOLEAN NOT NULL DEFAULT TRUE AFTER conectado');
            $pdo->exec("CREATE TABLE IF NOT EXISTS archivos (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                equipo_id BIGINT UNSIGNED NOT NULL,
                nombre VARCHAR(255) NOT NULL,
                checksum CHAR(64) NOT NULL UNIQUE,
                estado VARCHAR(30) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_archivos_equipo (equipo_id),
                CONSTRAINT fk_archivos_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("CREATE TABLE IF NOT EXISTS mediciones (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                archivo_id BIGINT UNSIGNED NOT NULL,
                equipo_id BIGINT UNSIGNED NOT NULL,
                measured_at DATETIME NOT NULL,
                tsf DECIMAL(16,6) NOT NULL,
                razon_oa DECIMAL(16,6) NOT NULL,
                conductividad DECIMAL(16,6) NOT NULL,
                estado VARCHAR(30) NOT NULL,
                archivo VARCHAR(255) NOT NULL,
                equipo VARCHAR(120) NOT NULL,
                INDEX idx_mediciones_fecha (measured_at),
                INDEX idx_mediciones_archivo (archivo_id),
                INDEX idx_mediciones_equipo (equipo_id),
                CONSTRAINT fk_mediciones_archivo FOREIGN KEY (archivo_id) REFERENCES archivos(id),
                CONSTRAINT fk_mediciones_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Conserva la primera lectura y elimina solamente copias idénticas.
            $pdo->exec("DELETE repetida FROM mediciones repetida
                INNER JOIN mediciones original
                    ON original.equipo_id = repetida.equipo_id
                    AND original.measured_at = repetida.measured_at
                    AND original.tsf = repetida.tsf
                    AND original.razon_oa = repetida.razon_oa
                    AND original.conductividad = repetida.conductividad
                    AND original.id < repetida.id");

            $oldUniqueIndex = $pdo->query("SELECT 1 FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                    AND table_name = 'mediciones'
                    AND index_name = 'uq_medicion_valores'
                LIMIT 1")->fetchColumn();
            if ($oldUniqueIndex) {
                $pdo->exec('ALTER TABLE mediciones DROP INDEX uq_medicion_valores');
            }
            $uniqueIndex = $pdo->query("SELECT 1 FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                    AND table_name = 'mediciones'
                    AND index_name = 'uq_medicion_equipo_valores'
                LIMIT 1")->fetchColumn();
            if (!$uniqueIndex) {
                $pdo->exec('ALTER TABLE mediciones ADD UNIQUE INDEX uq_medicion_equipo_valores (equipo_id, measured_at, tsf, razon_oa, conductividad)');
            }

            $pdo->beginTransaction();
            $equipment = $pdo->prepare('INSERT INTO equipos (nombre, identificador, conectado, last_seen_at) VALUES (:nombre, :identificador, 1, NOW()) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), nombre = VALUES(nombre), conectado = IF(activo=1,1,0), last_seen_at = NOW()');
            $equipment->execute(['nombre' => $equipmentName, 'identificador' => $identifier]);
            $equipmentId = (int) $pdo->lastInsertId();
            $receptionEnabled = $pdo->prepare('SELECT activo FROM equipos WHERE id=:id');
            $receptionEnabled->execute(['id' => $equipmentId]);
            if (!(bool)$receptionEnabled->fetchColumn()) {
                $pdo->commit();
                header('Content-Type: application/json');
                http_response_code(423);
                echo json_encode(['status'=>'paused','error'=>'La recepción de este equipo está pausada']);
                return;
            }
            $measuredAt = "{$fields[3]}-{$fields[2]}-{$fields[1]} {$normalizedTime}";
            $tsf = str_replace(',', '.', $fields[5]);
            $razon = str_replace(',', '.', $fields[6]);
            $conductividad = str_replace(',', '.', $fields[7]);

            $duplicate = $pdo->prepare('SELECT id FROM mediciones WHERE equipo_id = :equipo AND measured_at = :fecha AND tsf = :tsf AND razon_oa = :razon AND conductividad = :conductividad LIMIT 1');
            $duplicate->execute(['equipo' => $equipmentId, 'fecha' => $measuredAt, 'tsf' => $tsf, 'razon' => $razon, 'conductividad' => $conductividad]);
            if ($duplicate->fetchColumn()) {
                $pdo->commit();
                header('Content-Type: application/json');
                echo json_encode(['status' => 'duplicate', 'storage' => 'mysql']);
                return;
            }

            // Una línea es única dentro de su equipo, no entre equipos distintos.
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
                    'fecha' => $measuredAt,
                    'tsf' => $tsf, 'razon' => $razon,
                    'conductividad' => $conductividad,
                    'estado' => (float) $conductividad < 0 ? 'Revisar' : 'Válido', 'equipo' => $equipmentName,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            record_system_error($pdo??null,'API mediciones',sprintf('Error %s: %s',$error->getCode()?:'sin-código',$error->getMessage()));
            http_response_code(500); echo json_encode([
                'error' => 'No fue posible guardar la medición',
            ]); return;
        }
    } else {
        $target = dirname(__DIR__) . '/Analisis.txt';
        $current = is_file($target) ? file_get_contents($target) : '';
        if (!str_contains((string) $current, $line)) file_put_contents($target, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    header('Content-Type: application/json'); http_response_code(201); echo json_encode(['status' => 'created', 'storage' => $pdo instanceof PDO ? 'mysql' : 'txt']); return;
}

if ($path === '/api/agent/command' && $method === 'GET') {
    header('Content-Type: application/json');
    $agentApiKey = (string)(getenv('AGENT_API_KEY') ?: '');
    $sessionAuthorized = !empty($_SESSION['user']) && can('reader.use');
    $keyAuthorized = $agentApiKey !== '' && hash_equals($agentApiKey, $_SERVER['HTTP_X_API_KEY'] ?? '');
    if (!$sessionAuthorized && !$keyAuthorized) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); return; }
    $identifier = trim((string)($_GET['equipmentId'] ?? ''));
    if ($identifier === '') { http_response_code(422); echo json_encode(['error'=>'Identificador requerido']); return; }
    try {
        $connect = require dirname(__DIR__).'/config/database.php';
        $pdo = $connect();
        if (!$pdo instanceof PDO) throw new RuntimeException('Base de datos no disponible');
        $refreshColumn = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='equipos' AND column_name='refresh_requested_at' LIMIT 1")->fetchColumn();
        if (!$refreshColumn) $pdo->exec('ALTER TABLE equipos ADD refresh_requested_at DATETIME NULL, ADD refresh_acknowledged_at DATETIME NULL');
        $command = $pdo->prepare('SELECT id, activo, refresh_requested_at, refresh_acknowledged_at FROM equipos WHERE identificador=:identifier LIMIT 1');
        $command->execute(['identifier'=>$identifier]);
        $equipment = $command->fetch();
        if (!$equipment) { http_response_code(404); echo json_encode(['error'=>'Equipo no registrado']); return; }
        $refresh = $equipment['refresh_requested_at'] !== null && ($equipment['refresh_acknowledged_at'] === null || $equipment['refresh_requested_at'] > $equipment['refresh_acknowledged_at']);
        $pdo->prepare('UPDATE equipos SET last_seen_at=NOW(),conectado=1'.($refresh?',refresh_acknowledged_at=NOW()':'').' WHERE id=:id')->execute(['id'=>$equipment['id']]);
        echo json_encode(['status'=>'ok','active'=>(bool)$equipment['activo'],'refresh'=>$refresh]); return;
    } catch (Throwable $error) {
        record_system_error($pdo??null,'Órdenes remotas',$error->getMessage());
        http_response_code(500); echo json_encode(['error'=>'No fue posible consultar órdenes']); return;
    }
}

if ($path === '/api/agent/status' && $method === 'GET') {
    header('Content-Type: application/json');
    $agentApiKey = (string) (getenv('AGENT_API_KEY') ?: '');
    $sessionAuthorized = !empty($_SESSION['user']) && can('reader.use');
    $keyAuthorized = $agentApiKey !== '' && hash_equals($agentApiKey, $_SERVER['HTTP_X_API_KEY'] ?? '');
    if (!$sessionAuthorized && !$keyAuthorized) {
        http_response_code(401); echo json_encode(['error' => 'No autorizado']); return;
    }
    $identifier=trim((string)($_GET['equipmentId']??''));
    $equipmentName=trim((string)($_GET['equipmentName']??'Equipo Windows'));
    $newEquipment=false;
    if($identifier!==''){
        try{
            $connect=require dirname(__DIR__).'/config/database.php';$pdo=$connect();
            if($pdo instanceof PDO){
                $exists=$pdo->prepare('SELECT id FROM equipos WHERE identificador=:identificador LIMIT 1');$exists->execute(['identificador'=>$identifier]);$newEquipment=!$exists->fetchColumn();
                $register=$pdo->prepare('INSERT INTO equipos(nombre,identificador,conectado,last_seen_at) VALUES(:nombre,:identificador,1,NOW()) ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),conectado=1,last_seen_at=NOW()');
                $register->execute(['nombre'=>$equipmentName!==''?$equipmentName:'Equipo Windows','identificador'=>$identifier]);
            }
        }catch(Throwable$error){record_system_error($pdo??null,'Estado del lector',$error->getMessage());http_response_code(500);echo json_encode(['error'=>'No fue posible registrar el equipo']);return;}
    }
    echo json_encode(['status'=>'ok','serverTime'=>gmdate('c'),'newEquipment'=>$newEquipment]); return;
}

// El panel siempre requiere una sesión iniciada.
if (empty($_SESSION['user'])) { header('Location: ' . url('login')); return; }
$routePermissions=['/'=>'dashboard.view','/mediciones'=>'measurements.view','/archivos'=>'files.view','/equipos'=>'equipment.manage','/equipos/accion'=>'equipment.manage','/windows-reader'=>'reader.use','/usuarios'=>'users.manage','/usuarios/accion'=>'users.manage','/roles'=>'roles.manage','/roles/accion'=>'roles.manage','/permisos'=>'roles.manage','/errores'=>'errors.view','/errores/accion'=>'errors.view','/auditoria'=>'audit.view'];
$requiredPermission=$routePermissions[$path]??null;
if($requiredPermission!==null&&!can($requiredPermission)){$_SESSION['flashes']['error']='No tiene permiso para acceder a esta sección.';$fallback=can('reader.use')?url('windows-reader'):url();header('Location: '.$fallback);return;}

if ($path === '/') { (new App\Controllers\DashboardController())->index(); return; }
if ($path === '/equipos' && $method === 'GET') { (new App\Controllers\EquipmentController())->index(); return; }
if ($path === '/equipos/accion' && $method === 'POST') { (new App\Controllers\EquipmentController())->action(); return; }
if ($path === '/usuarios' && $method === 'GET') { (new App\Controllers\UserController())->index(); return; }
if ($path === '/usuarios/accion' && $method === 'POST') { (new App\Controllers\UserController())->action(); return; }
if ($path === '/roles' && $method === 'GET') { (new App\Controllers\RoleController())->index(); return; }
if ($path === '/roles/accion' && $method === 'POST') { (new App\Controllers\RoleController())->action(); return; }
if ($path === '/errores' && $method === 'GET') { (new App\Controllers\ErrorController())->index(); return; }
if ($path === '/errores/accion' && $method === 'POST') { (new App\Controllers\ErrorController())->action(); return; }
if ($path === '/permisos' && $method === 'GET') { header('Location: '.url('roles').'#permisos'); return; }
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
if ($path === '/configuracion') { header('Location: '.url()); return; }
$modules = [
    'mediciones' => ['key'=>'mediciones','title'=>'Mediciones','subtitle'=>'Consulta de variables procesadas'],
    'archivos' => ['key'=>'archivos','title'=>'Archivos','subtitle'=>'Resumen de archivos por equipo, con identificador, mediciones registradas y fecha de la última recepción.'],
    'equipos' => ['key'=>'equipos','title'=>'Equipos','subtitle'=>'Estado de los agentes locales'],
    'usuarios' => ['key'=>'usuarios','title'=>'Usuarios','subtitle'=>'Administración de accesos'],
    'roles' => ['key'=>'roles','title'=>'Roles','subtitle'=>'Perfiles de acceso del sistema'],
    'permisos' => ['key'=>'permisos','title'=>'Permisos','subtitle'=>'Acciones disponibles por rol'],
    'errores' => ['key'=>'errores','title'=>'Errores','subtitle'=>'Incidencias de procesamiento'],
    'auditoria' => ['key'=>'auditoria','title'=>'Auditoría','subtitle'=>'Trazabilidad de acciones'],
];
$key = ltrim($path, '/');
if (isset($modules[$key])) { (new App\Controllers\ModuleController())->index($modules[$key]); return; }
http_response_code(404); view('errors/404', ['title'=>'Página no encontrada','subtitle'=>'Error 404','active'=>'']);
