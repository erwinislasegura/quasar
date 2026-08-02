<section class="panel table-panel">
  <div class="panel-head"><div class="panel-title"><h3><?= e($module['title']) ?></h3><p><?= e($module['subtitle']) ?></p></div><button class="btn primary">Nuevo registro</button></div>
  <div class="table-tools"><label class="field has-icon"><input type="search" placeholder="Buscar en <?= e(strtolower($module['title'])) ?>..."></label><label class="field"><select><option>Todos los estados</option><option>Activo</option><option>Inactivo</option></select></label></div>
  <div class="table-scroll"><table><thead><tr><?php foreach (array_keys($rows[0] ?? []) as $column): ?><th><?= e($column) ?></th><?php endforeach; ?><th>Acciones</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $key => $value): ?><td><?= $key === 'Estado' ? '<span class="badge ok">' . e($value) . '</span>' : e($value) ?></td><?php endforeach; ?><td><a class="text-link" href="#">Ver detalle</a></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="table-footer"><span>Mostrando <?= count($rows) ?> de <?= count($rows) ?> registros</span><div class="pagination"><button class="page-btn active">1</button></div></div>
</section>
