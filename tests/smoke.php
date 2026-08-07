<?php
require dirname(__DIR__) . '/app/Models/MeasurementRepository.php';
$records = (new App\Models\MeasurementRepository())->all();
assert(count($records) === 82);
assert(array_keys($records[0]) === ['id','iso','fecha','hora','tiempo','razon','conductividad','archivo','equipo']);
assert($records[0]['fecha'] === '26-11-2025');
echo "OK: 82 mediciones procesadas\n";
