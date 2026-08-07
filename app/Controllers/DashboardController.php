<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\MeasurementRepository;

final class DashboardController
{
    public function index(): void
    {
        $connect = require dirname(__DIR__, 2) . '/config/database.php';
        $repository = new MeasurementRepository($connect());
        $selectedEquipment = trim((string) ($_GET['equipo'] ?? ''));
        $records = $repository->all($selectedEquipment);
        view('dashboard/index', [
            'title' => 'Panel de mediciones',
            'subtitle' => 'Información procesada automáticamente desde el archivo local',
            'active' => 'dashboard',
            'records' => $records,
            'equipmentOptions' => $repository->equipmentOptions(),
            'selectedEquipment' => $selectedEquipment,
            'dashboardScripts' => true,
        ]);
    }
}
