<?php
require dirname(__DIR__) . '/app/Models/MeasurementRepository.php';
$records = (new App\Models\MeasurementRepository())->all();
assert(count($records) === 82);
assert(array_keys($records[0]) === ['id','iso','fecha','hora','tiempo','razon','conductividad','archivo','equipo']);
assert($records[0]['fecha'] === '26-11-2025');

$temporaryFile = tempnam(sys_get_temp_dir(), 'quasar-hours-');
file_put_contents($temporaryFile, implode(PHP_EOL, [
    '17-01-2026-1:39:59;Tiempo;166,000000;Razon;0,764646;Conductividad;77,000000',
    '17-01-2026-04:39:59;Tiempo;169,300003;Razon;0,791089;Conductividad;120,000000',
    '17-01-2026-24:39:59;Tiempo;170,600006;Razon;0,797954;Conductividad;94,000000',
]));
$method = new ReflectionMethod(App\Models\MeasurementRepository::class, 'fromFile');
$parsedHours = $method->invoke(new App\Models\MeasurementRepository(), $temporaryFile);
unlink($temporaryFile);
assert(count($parsedHours) === 2);
assert($parsedHours[0]['hora'] === '01:39:59');
assert($parsedHours[0]['iso'] === '2026-01-17T01:39:59');
assert($parsedHours[1]['hora'] === '04:39:59');
echo "OK: 82 mediciones procesadas\n";
