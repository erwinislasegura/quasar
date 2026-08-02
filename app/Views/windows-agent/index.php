<section class="hero-strip">
  <div class="hero-copy">
    <small>Módulo de equipos</small>
    <h2>Desplegar agente de lectura en Windows</h2>
    <p>Descargue el agente y su configuración en el equipo que contiene Analisis.txt. El diseño y la operación del panel no se modifican.</p>
  </div>
  <div class="hero-file"><strong>QuasarAgent.ps1</strong><span>PowerShell 5.1 o posterior</span></div>
</section>

<section class="range-grid">
  <article class="panel range-card">
    <div class="range-head"><strong>1. Descargar agente</strong><span>PowerShell</span></div>
    <p class="kpi-foot">Guarde el script en una carpeta permanente del equipo Windows.</p>
    <a class="btn primary" href="/windows-agent/download?file=agent">Descargar QuasarAgent.ps1</a>
  </article>
  <article class="panel range-card">
    <div class="range-head"><strong>2. Descargar configuración</strong><span>JSON</span></div>
    <p class="kpi-foot">Renombre el archivo como config.json y complete la URL y la API key.</p>
    <a class="btn" href="/windows-agent/download?file=config">Descargar config.example.json</a>
  </article>
  <article class="panel range-card">
    <div class="range-head"><strong>3. Ejecutar</strong><span>Terminal</span></div>
    <p class="kpi-foot">Abra PowerShell en la carpeta descargada y ejecute el comando.</p>
    <code>powershell.exe -ExecutionPolicy Bypass -File .\QuasarAgent.ps1</code>
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
