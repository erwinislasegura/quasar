<section class="hero-strip">
  <div class="hero-copy">
    <small>Módulo de equipos</small>
    <h2>Desplegar agente de lectura en Windows</h2>
    <p>Instale el agente con un único archivo. El asistente comprueba la conexión, configura el equipo y deja la lectura iniciada automáticamente.</p>
  </div>
  <div class="hero-file"><strong>QuasarAgent.ps1</strong><span>PowerShell 5.1 o posterior</span></div>
</section>

<section class="range-grid">
  <article class="panel range-card">
    <div class="range-head"><strong>1. Descargar instalador</strong><span>Un solo archivo</span></div>
    <p class="kpi-foot">Descárguelo directamente en el computador que contiene Analisis.txt.</p>
    <a class="btn primary" href="<?= url('windows-agent/installer') ?>">Descargar Instalar-Quasar.ps1</a>
  </article>
  <article class="panel range-card">
    <div class="range-head"><strong>2. Abrir como administrador</strong><span>Windows</span></div>
    <p class="kpi-foot">Haga clic derecho en PowerShell, elija «Ejecutar como administrador» y abra la carpeta de descarga.</p>
    <code>cd $HOME\Downloads</code>
  </article>
  <article class="panel range-card">
    <div class="range-head"><strong>3. Seguir el asistente</strong><span>4 preguntas</span></div>
    <p class="kpi-foot">El asistente solicitará la ruta, nombre, identificador y clave. Después quedará funcionando solo.</p>
    <code>powershell.exe -ExecutionPolicy Bypass -File .\Instalar-Quasar.ps1</code>
  </article>
</section>

<section class="panel table-panel">
  <div class="panel-head"><div class="panel-title"><h3>Parámetros del despliegue</h3><p>Los valores deben coincidir con la configuración del servidor</p></div><span class="badge ok">Disponible</span></div>
  <div class="table-scroll"><table><thead><tr><th>Parámetro</th><th>Descripción</th><th>Ejemplo</th></tr></thead><tbody>
    <tr><td class="metric">sourceFile</td><td>Ruta del archivo observado</td><td>C:\SistemaTXT\Entrada\Analisis.txt</td></tr>
    <tr><td class="metric">apiUrl</td><td>Endpoint de recepción del servidor</td><td>https://servidor/api/measurements</td></tr>
    <tr><td class="metric">apiKey</td><td>Debe coincidir con AGENT_API_KEY</td><td>Clave privada del agente</td></tr>
    <tr><td class="metric">pollSeconds</td><td>Intervalo entre lecturas</td><td>5</td></tr>
  </tbody></table></div>
</section>

<section class="panel table-panel">
  <div class="panel-head"><div class="panel-title"><h3>¿Qué hace el instalador?</h3><p>Proceso automático, repetible y apto para cualquier cantidad de computadores</p></div></div>
  <div class="empty" style="text-align:left">
    <p>✓ Verifica que el archivo exista y que la clave permita conectar con este servidor.</p>
    <p>✓ Guarda el agente y su configuración en <code>C:\ProgramData\QuasarAgent</code>.</p>
    <p>✓ Crea una tarea de Windows que arranca con el sistema, incluso sin una sesión abierta.</p>
    <p>✓ Recuerda la última línea enviada y reintenta automáticamente cuando falla la red.</p>
  </div>
</section>
