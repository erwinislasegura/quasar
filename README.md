# Quasar · Analítica Local

Plataforma PHP MVC que convierte la referencia aprobada `index.html` en un panel dinámico y reutilizable, sin reemplazar su identidad visual. La copia inalterada está en `design-reference/index.html`.

La conversión directa solicitada está en `index.php`: contiene exactamente el HTML, CSS y JavaScript de `index.html`, pero obtiene `RAW_DATA` desde `Analisis.txt` mediante PHP. La ruta principal sirve este archivo sin envolverlo en otro layout, evitando cualquier diferencia visual.

## Ejecución local

Requiere PHP 8.1 o posterior.

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Abra `http://127.0.0.1:8080`. El panel siempre abre primero el login. Sin MySQL puede ingresar con `admin@quasar.local` y la contraseña definida en `ADMIN_PASSWORD` (`quasar123` de forma local). En producción debe cambiar esa contraseña.

En Apache también funciona dentro de un subdirectorio, por ejemplo `http://localhost/quasar/`: el `.htaccess` raíz prioriza PHP sobre `index.html`, abre el login y conserva `/quasar` en formularios, recursos y redirecciones. Después del acceso se muestra siempre el dashboard; si todavía no hay mediciones, sus paneles permanecen visibles con contadores en cero y el gráfico indica “Sin mediciones disponibles”.

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

La base completa actualizada para una instalación nueva se entrega en `database/quasar_updated.sql`.

Si la base de datos **ya está en uso**, no vuelva a importar el esquema completo. Edite las variables iniciales de `database/update_user.sql` y ejecute solamente ese archivo SQL:

```bash
mysql -u usuario -p quasar < database/update_user.sql
```

El SQL actualiza correo, nombre, rol, estado y `password_hash` dentro de una transacción, sin eliminar mediciones ni recrear tablas. El acceso inicial generado por los SQL es `admin@quasar.local` / `quasar123`; cambie esa contraseña después de ingresar.

Para incorporar a una base **ya existente** los nuevos menús, su catálogo y los permisos del Superadministrador, ejecute:

```bash
cd database
mysql -u usuario -p quasar < update_modules.sql
```

`update_modules.sql` es idempotente: crea la tabla `modulos`, registra las once opciones del sidebar, crea un permiso `*.view` para cada una y lo asigna al rol `Superadministrador` sin borrar información existente.

Los módulos administrativos no contienen filas de demostración: Usuarios, Roles, Permisos, Equipos, Archivos, Errores y Auditoría consultan directamente sus tablas MySQL. Mediciones consulta MySQL o `Analisis.txt` según el almacenamiento activo. Las búsquedas y filtros operan sobre los registros cargados; si MySQL no está configurado, el módulo lo informa y permanece vacío en vez de inventar información.

## Módulo de lectura para Windows

El panel ofrece un **Lector Windows** en `/windows-reader`. En cada computador Windows se abre la página con Microsoft Edge o Google Chrome, se selecciona `Analisis.txt` y se mantiene la pestaña abierta; no requiere instalar agentes, servicios ni tareas programadas. El navegador conserva la última línea confirmada y envía únicamente las líneas nuevas a `POST /api/measurements` usando la clave `AGENT_API_KEY`.

El nombre del equipo es una descripción elegida por el usuario. El identificador se crea una sola vez para cada computador (la pantalla permite generarlo) y no debe ser un correo. La clave de conexión es el valor secreto de `AGENT_API_KEY` configurado por el administrador en el entorno del servidor; no se genera ni se muestra en el navegador.

### Configurar la clave en cPanel

1. Abra **cPanel → Terminal** y genere una clave aleatoria (también puede usar el generador de contraseñas de cPanel):

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

2. Abra **cPanel → Administrador de archivos**, active «Mostrar archivos ocultos» y edite el `.htaccess` que está en la raíz de Quasar (el mismo nivel que `index.php`). Agregue, reemplazando el ejemplo por la clave generada:

   ```apache
   <IfModule mod_env.c>
       SetEnv AGENT_API_KEY "PEGUE_AQUI_SU_CLAVE_ALEATORIA"
   </IfModule>
   ```

3. Guarde el archivo. En Quasar, cierre y vuelva a abrir **Configuración**: `AGENT_API_KEY` debe aparecer como **Configurada**.
4. Abra **Lector Windows** en cada PC y pegue exactamente esa misma clave en **Clave de conexión**. El nombre puede ser descriptivo y el identificador debe ser único por PC.

No agregue la clave a `.env.example`, no la publique ni use la contraseña del panel. Este proyecto lee `AGENT_API_KEY` desde el entorno real de PHP; el archivo `.env.example` solo documenta los nombres de las variables. Si al agregar `SetEnv` cPanel muestra un error 500, elimine esas líneas y solicite al proveedor que defina `AGENT_API_KEY` para la aplicación PHP, porque el hosting ha deshabilitado `mod_env` o esa directiva.
