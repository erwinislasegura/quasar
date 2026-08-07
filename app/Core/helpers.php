<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $directory = rtrim(dirname($script), '/.');
    if (str_ends_with($directory, '/public')) $directory = substr($directory, 0, -7);
    return ($directory ?: '') . '/' . ltrim($path, '/');
}

function view(string $view, array $data = [], string $layout = 'admin'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(404);
        $viewFile = dirname(__DIR__) . '/Views/errors/404.php';
    }
    ob_start();
    require $viewFile;
    $content = (string) ob_get_clean();
    require dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['_csrf'];
}

function can(string $permission): bool
{
    if (($_SESSION['user']['role'] ?? '') === 'Superadministrador') return true;
    return in_array($permission, $_SESSION['user']['permissions'] ?? [], true);
}

function audit_event(PDO $pdo, string $action, array $context = []): void
{
    try {
        $statement=$pdo->prepare('INSERT INTO auditoria(usuario_id,accion,contexto) SELECT id,:accion,:contexto FROM usuarios WHERE email=:email');
        $statement->execute(['accion'=>$action,'contexto'=>json_encode($context,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'email'=>$_SESSION['user']['email']??'']);
    } catch(Throwable $error) { error_log('[Quasar Audit] '.$error->getMessage()); }
}

function record_system_error(?PDO $pdo, string $origin, string $detail, string $status = 'Pendiente'): void
{
    error_log('[Quasar '.$origin.'] '.$detail);
    if(!$pdo instanceof PDO)return;
    try{$statement=$pdo->prepare('INSERT INTO errores(origen,detalle,estado) VALUES(:origen,:detalle,:estado)');$statement->execute(['origen'=>$origin,'detalle'=>$detail,'estado'=>$status]);}
    catch(Throwable $error){error_log('[Quasar Error Registry] '.$error->getMessage());}
}
