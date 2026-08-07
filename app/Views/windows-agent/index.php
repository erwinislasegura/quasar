<section class="hero-strip">
  <div class="hero-copy">
    <small>Lector web para Windows</small>
    <h2>Conectar este computador sin instalar un agente</h2>
    <p>Seleccione el archivo desde Microsoft Edge o Google Chrome y mantenga esta ventana abierta. Quasar enviará automáticamente las líneas nuevas.</p>
  </div>
  <div class="hero-file"><strong>Sin instalación</strong><span>Ventana de navegador</span></div>
</section>

<section class="panel table-panel web-reader" data-status-url="<?= e(url('api/agent/status')) ?>" data-command-url="<?= e(url('api/agent/command')) ?>" data-measurements-url="<?= e(url('api/measurements')) ?>" data-sw-url="<?= e(url('sw.js')) ?>">
  <div class="panel-head">
    <div class="panel-title"><h3>Configurar lector de este equipo</h3><p>El equipo, el intervalo y la ruta quedan registrados en este navegador.</p></div>
    <span class="badge warn" id="readerBadge">Sin configurar</span>
  </div>

  <div class="reader-help" role="note" aria-label="Cómo completar la configuración">
    <strong>¿De dónde obtengo estos datos?</strong>
    <ul>
      <li><b>Nombre del equipo:</b> usted lo elige para reconocer el computador en el panel, por ejemplo <code>Laboratorio principal</code>.</li>
      <li><b>Identificador único:</b> no es un correo ni una contraseña. Cree uno que no se repita, por ejemplo <code>planta-pc-01</code>, o use el botón <b>Generar</b>.</li>
      <li><b>Intervalo:</b> elija cada cuánto revisar el TXT; se recomiendan 5 o 10 segundos.</li>
    </ul>
  </div>

  <div class="reader-form">
    <label>Nombre del equipo<input id="readerEquipmentName" value="Equipo Windows" autocomplete="off"><small>Nombre descriptivo elegido por usted.</small></label>
    <label>Identificador único<span class="reader-input-action"><input id="readerEquipmentId" placeholder="Ejemplo: planta-pc-01" autocomplete="off"><button class="btn" type="button" id="readerGenerateId">Generar</button></span><small>No use su correo. Debe ser distinto para cada computador.</small></label>
    <label>Intervalo de lectura<select id="readerInterval"><option value="3">3 segundos</option><option value="5" selected>5 segundos</option><option value="10">10 segundos</option><option value="custom">Personalizado</option></select></label>
    <label id="readerCustomIntervalField" hidden>Segundos personalizados<input id="readerCustomInterval" type="number" min="1" max="3600" value="15" inputmode="numeric"><small>Entre 1 y 3600 segundos</small></label>
  </div>

  <div class="reader-file-box">
    <div><small>Archivo seleccionado</small><strong id="readerFileName">Ninguno</strong><span id="readerFileMeta">Seleccione Analisis.txt para continuar</span></div>
    <button class="btn" type="button" id="readerSelectFile">Seleccionar Analisis.txt</button>
  </div>

  <div class="reader-actions">
    <button class="btn" type="button" id="readerInstall" hidden>Instalar aplicación</button>
    <button class="btn primary" type="button" id="readerStart" disabled>Iniciar lectura</button>
    <button class="btn" type="button" id="readerStop" disabled>Detener</button>
    <button class="btn" type="button" id="readerReset">Reenviar desde el inicio</button>
  </div>
  <p class="reader-message" id="readerMessage">Complete los datos y seleccione el archivo.</p>

  <div class="range-grid reader-stats reader-stats-four">
    <div class="range-card"><small>Estado</small><b id="readerState">Detenido</b></div>
    <div class="range-card"><small>Líneas enviadas</small><b id="readerSent">0</b></div>
    <div class="range-card"><small>Última línea confirmada</small><b id="readerConfirmed">0</b></div>
    <div class="range-card"><small>Última revisión</small><b id="readerLastCheck">—</b></div>
  </div>
</section>

<section class="panel table-panel">
  <div class="panel-head"><div class="panel-title"><h3>Uso diario</h3><p>Solo necesita mantener esta página abierta</p></div><span class="badge ok">Edge / Chrome</span></div>
  <div class="empty" style="text-align:left">
    <p>1. Abra esta página en el computador donde se genera <code>Analisis.txt</code>.</p>
    <p>2. Complete los datos, seleccione el archivo y pulse <strong>Iniciar lectura</strong>.</p>
    <p>3. Mantenga esta pestaña abierta. Si Windows o el navegador se reinician, la configuración y la ruta se recuperan automáticamente; por seguridad, el navegador puede pedir confirmar el acceso al archivo.</p>
    <p>4. El lector recuerda la última línea confirmada y no vuelve a enviar mediciones anteriores.</p>
    <p><strong>Importante:</strong> publique Quasar con HTTPS. Los navegadores solo permiten seleccionar archivos desde una página segura (o desde localhost).</p>
  </div>
</section>

<script src="<?= e(url('assets/js/windows-reader.js')) ?>?v=20260802-6"></script>
