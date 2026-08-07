<section class="panel management-create">
  <div class="panel-head"><div class="panel-title"><h3>Crear usuario</h3><p>Registre una cuenta y asigne su nivel de acceso.</p></div><span class="management-total"><?= count($users) ?> <small>usuarios</small></span></div>
  <form method="post" action="<?= url('usuarios/accion') ?>" class="user-create-form">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create">
    <label><span>Nombre</span><input name="nombre" maxlength="120" required placeholder="Nombre completo"></label>
    <label><span>Correo</span><input name="email" type="email" maxlength="190" required placeholder="usuario@empresa.cl"></label>
    <label><span>Rol</span><select name="rol_id" required><?php foreach($roles as$role):?><option value="<?= (int)$role['id'] ?>"><?= e($role['nombre']) ?></option><?php endforeach;?></select></label>
    <label><span>Contraseña</span><input name="password" type="password" minlength="10" required placeholder="Mínimo 10 caracteres"></label>
    <button class="btn primary" type="submit">Crear usuario</button>
  </form>
</section>

<section class="panel management-panel">
  <div class="panel-head"><div class="panel-title"><h3>Usuarios registrados</h3><p>Accesos, roles y estado de las cuentas del sistema.</p></div></div>
  <div class="management-list">
    <?php foreach($users as$user):$isCurrent=$user['email']===($_SESSION['user']['email']??'');?>
      <article class="management-row <?= (int)$user['activo']===1?'':'is-disabled' ?>">
        <div class="management-summary user-summary">
          <div class="management-identity"><span class="user-avatar"><?= e(mb_strtoupper(mb_substr($user['nombre'],0,1))) ?></span><div><strong><?= e($user['nombre']) ?></strong><small><?= e($user['email']) ?></small></div></div>
          <div class="management-field"><small>Rol asignado</small><strong><?= e($user['rol']) ?></strong></div>
          <div class="management-field"><small>Estado</small><span class="status-text <?= (int)$user['activo']===1?'active':'inactive' ?>"><?= (int)$user['activo']===1?'Activo':'Desactivado' ?></span></div>
          <div class="management-buttons">
            <button class="btn record-edit-trigger" type="button">Editar</button>
            <?php if(!$isCurrent):?><form method="post" action="<?= url('usuarios/accion') ?>"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn" type="submit"><?= (int)$user['activo']===1?'Desactivar':'Activar' ?></button></form><form method="post" action="<?= url('usuarios/accion') ?>" onsubmit="return confirm('¿Eliminar este usuario definitivamente?');"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><input type="hidden" name="action" value="delete"><button class="icon-danger-btn" type="submit" aria-label="Eliminar usuario">Eliminar</button></form><?php else:?><span class="current-session">Sesión actual</span><?php endif;?>
          </div>
        </div>
        <div class="record-editor-content">
          <form method="post" action="<?= url('usuarios/accion') ?>" class="compact-edit-form"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><label><span>Nombre</span><input name="nombre" maxlength="120" required value="<?= e($user['nombre']) ?>"></label><label><span>Correo</span><input name="email" type="email" maxlength="190" required value="<?= e($user['email']) ?>"></label><label><span>Rol</span><select name="rol_id"><?php foreach($roles as$role):?><option value="<?= (int)$role['id'] ?>" <?= (int)$role['id']===(int)$user['rol_id']?'selected':'' ?>><?= e($role['nombre']) ?></option><?php endforeach;?></select></label><label><span>Nueva contraseña</span><input name="password" type="password" minlength="10" placeholder="Opcional"></label><button class="btn primary" type="submit">Guardar cambios</button></form>
        </div>
      </article>
    <?php endforeach;?>
  </div>
</section>
<script>document.querySelectorAll('.record-edit-trigger').forEach(button=>button.addEventListener('click',()=>{const row=button.closest('.management-row');row.classList.toggle('editing');button.textContent=row.classList.contains('editing')?'Cerrar':'Editar';}));</script>
