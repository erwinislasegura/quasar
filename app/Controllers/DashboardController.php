<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MeasurementRepository;

final class DashboardController
{
    public function index(): void
    {
        $records = (new MeasurementRepository())->all();
        view('dashboard/index', [
            'title' => 'Panel de mediciones',
            'subtitle' => 'Información procesada automáticamente desde el archivo local',
            'active' => 'dashboard',
            'records' => $records,
            'dashboardScripts' => true,
        ]);
    }
}
