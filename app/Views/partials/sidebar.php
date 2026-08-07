<?php
$menu = [
  'Principal' => ['dashboard' => ['Panel', '/'], 'mediciones' => ['Mediciones', '/mediciones'], 'archivos' => ['Archivos', '/archivos'], 'equipos' => ['Equipos', '/equipos'], 'windows-reader' => ['Lector Windows', '/windows-reader']],
  'Administración' => ['usuarios' => ['Usuarios', '/usuarios'], 'roles' => ['Roles y permisos', '/roles']],
  'Sistema' => ['errores' => ['Errores', '/errores'], 'auditoria' => ['Auditoría', '/auditoria']],
];
$menuPermissions = ['dashboard'=>'dashboard.view','mediciones'=>'measurements.view','archivos'=>'files.view','equipos'=>'equipment.manage','windows-reader'=>'reader.use','usuarios'=>'users.manage','roles'=>'roles.manage','errores'=>'errors.view','auditoria'=>'audit.view'];
foreach($menu as$section=>$items){foreach($items as$key=>$item){if(!can($menuPermissions[$key]))unset($menu[$section][$key]);}if(!$menu[$section])unset($menu[$section]);}
$icons = [
  'dashboard' => '<path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/>',
  'mediciones' => '<path d="M4 19V5m0 14h16M7 15l3-4 3 2 4-6"/><circle cx="7" cy="15" r="1"/><circle cx="17" cy="7" r="1"/>',
  'archivos' => '<path d="M4 7h6l2 2h8v10H4V7Z"/><path d="M4 7V5h7l2 2"/>',
  'equipos' => '<rect x="3" y="4" width="18" height="13" rx="1"/><path d="M8 21h8m-4-4v4M7 8h4"/>',
  'windows-reader' => '<path d="M3 5.5 11 4v7H3V5.5Zm10-1.7L21 3v8h-8V3.8ZM3 13h8v7l-8-1.5V13Zm10 0h8v8l-8-.8V13Z"/>',
  'usuarios' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 20v-2a5.5 5.5 0 0 1 11 0v2M16 8h5m-2.5-2.5v5"/>',
  'roles' => '<path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
  'permisos' => '<circle cx="8" cy="15" r="3"/><path d="m10.5 13.5 7-7a2.1 2.1 0 1 1 3 3l-7 7M16 8l2 2"/>',
  'errores' => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5m0 3h.01"/>',
  'auditoria' => '<path d="M5 4h14v16H5z"/><path d="M8 8h8m-8 4h8m-8 4h5"/>',
  'configuracion' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1v.1h-4v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.6-1H3v-4h.1A1.7 1.7 0 0 0 4.6 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6V3h4v.1A1.7 1.7 0 0 0 15 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
];
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img class="brand-logo" src="<?= e(url('assets/image(52).png')) ?>" alt="QuasarTech Tecnologías Aplicadas">
  </div>
  <?php foreach ($menu as $section => $items): ?>
    <div class="nav-label"><?= e($section) ?></div>
    <nav class="nav">
      <?php foreach ($items as $key => [$label, $href]): ?>
        <a href="<?= e(url($href)) ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $icons[$key] ?? '' ?></svg><span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endforeach; ?>
</aside>
