<section class="hero-strip">
  <div class="hero-copy">
    <small>Lector web para Windows</small>
    <h2>Conectar este computador sin instalar un agente</h2>
    <p>Seleccione el archivo desde Microsoft Edge o Google Chrome y mantenga esta ventana abierta. Quasar enviará automáticamente las líneas nuevas.</p>
  </div>
  <div class="hero-file"><strong>Sin instalación</strong><span>Ventana de navegador</span></div>
</section>

<section class="panel table-panel web-reader" data-status-url="<?= e(url('api/agent/status')) ?>" data-measurements-url="<?= e(url('api/measurements')) ?>">
  <div class="panel-head">
    <div class="panel-title"><h3>Configurar lector de este equipo</h3><p>Los datos permanecen en este navegador y puede cambiarlos cuando sea necesario.</p></div>
    <span class="badge warn" id="readerBadge">Sin configurar</span>
  </div>

  <div class="reader-form">
    <label>Nombre del equipo<input id="readerEquipmentName" value="Equipo Windows" autocomplete="off"></label>
    <label>Identificador único<input id="readerEquipmentId" placeholder="Ejemplo: planta-equipo-01" autocomplete="off"></label>
    <label>Clave de conexión<input id="readerApiKey" type="password" placeholder="Clave configurada en el servidor" autocomplete="off"></label>
    <label>Intervalo de lectura<select id="readerInterval"><option value="3">3 segundos</option><option value="5" selected>5 segundos</option><option value="10">10 segundos</option></select></label>
  </div>

  <div class="reader-file-box">
    <div><small>Archivo seleccionado</small><strong id="readerFileName">Ninguno</strong><span id="readerFileMeta">Seleccione Analisis.txt para continuar</span></div>
    <button class="btn" type="button" id="readerSelectFile">Seleccionar Analisis.txt</button>
  </div>

  <div class="reader-actions">
    <button class="btn primary" type="button" id="readerStart" disabled>Iniciar lectura</button>
    <button class="btn" type="button" id="readerStop" disabled>Detener</button>
    <button class="btn" type="button" id="readerReset">Reenviar desde el inicio</button>
  </div>
  <p class="reader-message" id="readerMessage">Complete los datos y seleccione el archivo.</p>

  <div class="range-grid reader-stats">
    <div class="range-card"><small>Estado</small><b id="readerState">Detenido</b></div>
    <div class="range-card"><small>Líneas enviadas</small><b id="readerSent">0</b></div>
    <div class="range-card"><small>Última revisión</small><b id="readerLastCheck">—</b></div>
  </div>
</section>

<section class="panel table-panel">
  <div class="panel-head"><div class="panel-title"><h3>Uso diario</h3><p>Solo necesita mantener esta página abierta</p></div><span class="badge ok">Edge / Chrome</span></div>
  <div class="empty" style="text-align:left">
    <p>1. Abra esta página en el computador donde se genera <code>Analisis.txt</code>.</p>
    <p>2. Complete los datos, seleccione el archivo y pulse <strong>Iniciar lectura</strong>.</p>
    <p>3. Mantenga esta pestaña abierta. Si Windows o el navegador se reinician, vuelva a seleccionar el archivo y pulse iniciar.</p>
    <p>4. El lector recuerda la última línea confirmada y no vuelve a enviar mediciones anteriores.</p>
    <p><strong>Importante:</strong> publique Quasar con HTTPS. Los navegadores solo permiten seleccionar archivos desde una página segura (o desde localhost).</p>
  </div>
</section>

<script src="<?= e(url('assets/js/windows-reader.js')) ?>"></script>
