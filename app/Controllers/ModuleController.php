<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AdminModuleRepository;

final class ModuleController
{
    /** @param array<string, string> $module */
    public function index(array $module): void
    {
        $connect = require dirname(__DIR__, 2) . '/config/database.php';
        $pdo = $connect();
        $databaseConnected = $pdo instanceof \PDO;
        $rows = (new AdminModuleRepository($pdo))->rows($module['key']);
        view('partials/module-table', [
            'title' => $module['title'], 'subtitle' => $module['subtitle'],
            'active' => $module['key'], 'module' => $module,
            'rows' => $rows, 'databaseConnected' => $databaseConnected,
        ]);
    }
}
