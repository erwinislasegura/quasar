# Quasar · Analítica Local

Plataforma PHP MVC que convierte la referencia aprobada `index.html` en un panel dinámico y reutilizable, sin reemplazar su identidad visual. La copia inalterada está en `design-reference/index.html`.

La conversión directa solicitada está en `index.php`: contiene exactamente el HTML, CSS y JavaScript de `index.html`, pero obtiene `RAW_DATA` desde `Analisis.txt` mediante PHP. La ruta principal sirve este archivo sin envolverlo en otro layout, evitando cualquier diferencia visual.

## Ejecución local

Requiere PHP 8.1 o posterior.

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Abra `http://127.0.0.1:8080`. Para exigir autenticación, ejecute con `REQUIRE_AUTH=1`; el usuario de demostración es `admin@quasar.local` y la contraseña predeterminada es `quasar123` (se puede reemplazar mediante `ADMIN_PASSWORD`).

## Arquitectura

- `public/index.php`: front controller y rutas.
- `app/Controllers`: controladores del dashboard y módulos.
- `app/Models/MeasurementRepository.php`: acceso PDO preparado para MySQL y lector local de respaldo.
- `app/Views/layouts` y `app/Views/partials`: layout, encabezado, menú, alertas y pie reutilizables.
- `public/assets`: CSS y JavaScript derivados de la referencia.
- `database/schema.sql`: esquema inicial MySQL para mediciones, archivos, equipos, seguridad y auditoría.

Los módulos disponibles son Dashboard, Mediciones, Archivos, Equipos, Usuarios, Roles, Permisos, Errores, Configuración y Auditoría. El panel admite búsqueda, filtros, orden, paginación, exportación CSV, gráfico y navegación responsive.
