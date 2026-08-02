<header class="topbar">
  <button class="menu-button" id="menuButton" aria-label="Abrir menú"><svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
  <div class="page-title"><h1><?= e($title ?? '') ?></h1><p><?= e($subtitle ?? '') ?></p></div>
  <div class="top-actions">
    <div class="last-update">Última actualización<br><strong id="lastReadText"><?= date('d-m-Y · H:i:s') ?></strong></div>
    <?php if (($active ?? '') === 'dashboard'): ?><button class="btn" id="exportButton"><span>Exportar CSV</span></button><button class="btn primary" id="refreshButton"><span>Actualizar</span></button><?php endif; ?>
    <div class="user-chip"><div><strong><?= e($_SESSION['user']['name'] ?? 'Administrador local') ?></strong><span><?= e($_SESSION['user']['role'] ?? 'Administrador') ?></span></div><a class="text-link" href="/logout">Salir</a></div>
  </div>
</header>
