<?php
$menu = [
  'Principal' => ['dashboard' => ['Panel', '/'], 'mediciones' => ['Mediciones', '/mediciones'], 'archivos' => ['Archivos', '/archivos'], 'equipos' => ['Equipos', '/equipos']],
  'Administración' => ['usuarios' => ['Usuarios', '/usuarios'], 'roles' => ['Roles', '/roles'], 'permisos' => ['Permisos', '/permisos']],
  'Sistema' => ['errores' => ['Errores', '/errores'], 'auditoria' => ['Auditoría', '/auditoria'], 'configuracion' => ['Configuración', '/configuracion']],
];
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5M4 19h16"/><path d="m7 15 3-4 3 2 4-6"/><circle cx="7" cy="15" r="1"/><circle cx="10" cy="11" r="1"/><circle cx="13" cy="13" r="1"/><circle cx="17" cy="7" r="1"/></svg></div>
    <div><strong>Analítica Local</strong><small>Monitor automático TXT</small></div>
  </div>
  <?php foreach ($menu as $section => $items): ?>
    <div class="nav-label"><?= e($section) ?></div>
    <nav class="nav">
      <?php foreach ($items as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16"/><path d="M8 9h8M8 13h8M8 17h5"/></svg><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endforeach; ?>
  <div class="source-card"><div class="status"><span class="pulse"></span> Lectura activa</div><code>C:\SistemaTXT\Entrada\Analisis.txt</code></div>
</aside>
