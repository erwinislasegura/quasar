# Quasar · Analítica Local

Plataforma PHP MVC que convierte la referencia aprobada `index.html` en un panel dinámico y reutilizable, sin reemplazar su identidad visual. La copia inalterada está en `design-reference/index.html`.

La conversión directa solicitada está en `index.php`: contiene exactamente el HTML, CSS y JavaScript de `index.html`, pero obtiene `RAW_DATA` desde `Analisis.txt` mediante PHP. La ruta principal sirve este archivo sin envolverlo en otro layout, evitando cualquier diferencia visual.

## Ejecución local

Requiere PHP 8.1 o posterior.

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Abra `http://127.0.0.1:8080`. El panel siempre abre primero el login. Sin MySQL puede ingresar con `admin@quasar.local` y la contraseña definida en `ADMIN_PASSWORD` (`quasar123` de forma local). En producción debe cambiar esa contraseña.

## Arquitectura

- `public/index.php`: front controller y rutas.
- `app/Controllers`: controladores del dashboard y módulos.
- `app/Models/MeasurementRepository.php`: acceso PDO preparado para MySQL y lector local de respaldo.
- `app/Views/layouts` y `app/Views/partials`: layout, encabezado, menú, alertas y pie reutilizables.
- `public/assets`: CSS y JavaScript derivados de la referencia.
- `database/schema.sql`: esquema inicial MySQL para mediciones, archivos, equipos, seguridad y auditoría.
- `config/database.php`: conexión PDO centralizada mediante variables de entorno.

Los módulos disponibles son Dashboard, Mediciones, Archivos, Equipos, Usuarios, Roles, Permisos, Errores, Configuración y Auditoría. El panel admite búsqueda, filtros, orden, paginación, exportación CSV, gráfico y navegación responsive.

## Conexión a MySQL

La conexión se encuentra aislada en `config/database.php`; no contiene credenciales dentro del código. Configure las variables mostradas en `.env.example` en el entorno del servidor. Si `DB_HOST` no está definido, la aplicación continúa leyendo `Analisis.txt`. Cuando está definido, el repositorio utiliza PDO y la tabla `mediciones` de `database/schema.sql`.

Después de importar `database/schema.sql`, cree o actualice el superusuario ejecutando:

```bash
php database/create_superuser.php admin@quasar.local 'una-clave-segura' 'Superusuario'
```

Si la base de datos **ya está en uso**, no vuelva a importar el esquema. Actualice el usuario existente con el script transaccional `database/update_user.php`; solamente modifica los campos indicados:

```bash
php database/update_user.php --email=admin@quasar.local --name="Administrador principal" --password="una-clave-nueva-segura" --role=Superadministrador --enable
```

También permite cambiar el correo con `--new-email`, desactivar con `--disable` y consultar todas las opciones mediante `php database/update_user.php --help`. La contraseña nunca se guarda en texto plano: el script genera `password_hash` antes de actualizarla.

## Módulo de lectura para Windows

El agente solicitado está en `windows-agent/QuasarAgent.ps1`. Su instalación y configuración se explican en `windows-agent/README.md`. El agente lee las nuevas líneas del TXT, conserva su posición y las envía a `POST /api/measurements` usando la clave `AGENT_API_KEY`.
