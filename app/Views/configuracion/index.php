<section class="hero-strip">
  <div class="hero-copy"><small>Administración del sistema</small><h2>Configuración de servicios</h2><p>Estado de la base de datos, recepción del agente Windows y archivo local.</p></div>
  <div class="hero-file"><strong><?= $databaseConnected ? 'MySQL conectado' : 'Modo TXT local' ?></strong><span>Almacenamiento activo</span></div>
</section>

<section class="range-grid">
  <article class="panel range-card"><div class="range-head"><strong>Base de datos</strong><span class="badge <?= $databaseConnected ? 'ok' : 'warn' ?>"><?= $databaseConnected ? 'Conectada' : 'Sin conexión' ?></span></div><div class="range-values"><div><small>Servidor</small><b><?= e(getenv('DB_HOST') ?: 'No configurado') ?></b></div></div><p class="kpi-foot">Se configura mediante las variables DB_* de <code>.env.example</code>.</p></article>
  <article class="panel range-card"><div class="range-head"><strong>API del lector</strong><span class="badge <?= $agentKeyConfigured ? 'ok' : 'warn' ?>"><?= $agentKeyConfigured ? 'Protegida' : 'Clave predeterminada' ?></span></div><div class="range-values"><div><small>Endpoint</small><b>/api/measurements</b></div></div><a class="btn primary" href="<?= url('windows-reader') ?>">Abrir Lector Windows</a></article>
  <article class="panel range-card"><div class="range-head"><strong>Archivo local</strong><span class="badge ok">Disponible</span></div><div class="range-values"><div><small>Ruta</small><b>Analisis.txt</b></div></div><p class="kpi-foot">Se utiliza automáticamente cuando MySQL no está configurado.</p></article>
</section>

<section class="panel table-panel"><div class="panel-head"><div class="panel-title"><h3>Variables requeridas</h3><p>Defínalas en Apache, PHP-FPM o el entorno del servidor</p></div></div><div class="table-scroll"><table><thead><tr><th>Variable</th><th>Función</th><th>Estado</th></tr></thead><tbody>
<tr><td class="metric">DB_HOST</td><td>Activa almacenamiento MySQL</td><td><span class="badge <?= $databaseConnected ? 'ok' : 'warn' ?>"><?= $databaseConnected ? 'Configurada' : 'Opcional' ?></span></td></tr>
<tr><td class="metric">AGENT_API_KEY</td><td>Protege los envíos desde Windows</td><td><span class="badge <?= $agentKeyConfigured ? 'ok' : 'error' ?>"><?= $agentKeyConfigured ? 'Configurada' : 'Debe configurarse' ?></span></td></tr>
<tr><td class="metric">ADMIN_PASSWORD</td><td>Acceso local inicial sin MySQL</td><td><span class="badge ok">Disponible</span></td></tr>
</tbody></table></div></section>
