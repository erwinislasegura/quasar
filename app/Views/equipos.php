<section class="panel management-panel">
  <div class="panel-head"><div class="panel-title"><h3>Equipos registrados</h3><p>Control de identificación, conexión y recepción de mediciones.</p></div><span class="management-total"><?= count($rows) ?> <small>equipos</small></span></div>
  <div class="management-note"><strong>Pausa segura:</strong> detiene nuevas mediciones sin eliminar el historial del equipo.</div>
  <div class="management-list">
    <?php foreach($rows as$equipment):$active=(int)$equipment['activo']===1;?>
      <article class="management-row <?= $active?'':'is-disabled' ?>">
        <div class="management-summary equipment-summary">
          <div class="management-identity"><span class="device-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="13"/><path d="M8 21h8m-4-4v4"/></svg></span><div><strong><?= e($equipment['nombre']) ?></strong><small><?= e($equipment['identificador']) ?></small></div></div>
          <div class="management-field"><small>Mediciones</small><strong><?= (int)$equipment['mediciones'] ?></strong></div>
          <div class="management-field"><small>Última conexión</small><strong><?= e($equipment['last_seen_at']?:'Sin conexión') ?></strong></div>
          <div class="management-field"><small>Conexión</small><span class="status-text <?= (int)$equipment['conectado']===1?'active':'inactive' ?>"><?= (int)$equipment['conectado']===1?'Conectado':'Sin conexión' ?></span></div>
          <div class="management-field"><small>Recepción</small><span class="status-text <?= $active?'active':'paused' ?>"><?= $active?'Habilitada':'Pausada' ?></span></div>
          <div class="management-buttons"><button class="btn record-edit-trigger" type="button">Editar</button><?php if($active):?><form method="post" action="<?= url('equipos/accion') ?>"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$equipment['id'] ?>"><input type="hidden" name="action" value="refresh"><button class="btn" type="submit">Actualizar lector</button></form><?php endif;?><form method="post" action="<?= url('equipos/accion') ?>"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$equipment['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn" type="submit"><?= $active?'Pausar':'Reanudar' ?></button></form><form method="post" action="<?= url('equipos/accion') ?>" onsubmit="return confirm('¿Eliminar este equipo y todas sus mediciones?');"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$equipment['id'] ?>"><input type="hidden" name="action" value="delete"><button class="icon-danger-btn" type="submit">Eliminar</button></form></div>
        </div>
        <div class="record-editor-content"><form method="post" action="<?= url('equipos/accion') ?>" class="compact-edit-form equipment-compact-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$equipment['id'] ?>"><input type="hidden" name="action" value="update"><label><span>Nombre del equipo</span><input name="nombre" maxlength="120" required value="<?= e($equipment['nombre']) ?>"></label><label><span>Identificador</span><input name="identificador" maxlength="120" pattern="[A-Za-z0-9._-]+" required value="<?= e($equipment['identificador']) ?>"></label><button class="btn primary" type="submit">Guardar cambios</button></form><p>Si cambia el identificador, actualícelo también en el lector Windows de este equipo.</p></div>
      </article>
    <?php endforeach;?>
    <?php if(!$rows):?><div class="empty">No hay equipos registrados.</div><?php endif;?>
  </div>
</section>
<script>document.querySelectorAll('.record-edit-trigger').forEach(button=>button.addEventListener('click',()=>{const row=button.closest('.management-row');row.classList.toggle('editing');button.textContent=row.classList.contains('editing')?'Cerrar':'Editar';}));</script>
