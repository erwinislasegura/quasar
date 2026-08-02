<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MeasurementRepository;

final class DashboardController
{
    public function index(): void
    {
        $connect = require dirname(__DIR__, 2) . '/config/database.php';
        $records = (new MeasurementRepository($connect()))->all();
        view('dashboard/index', [
            'title' => 'Panel de mediciones',
            'subtitle' => 'Información procesada automáticamente desde el archivo local',
            'active' => 'dashboard',
            'records' => $records,
            'dashboardScripts' => true,
        ]);
    }
}
