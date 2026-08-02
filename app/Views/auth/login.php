<main class="login-page">
  <section class="login-card">
    <div class="brand">
      <div class="brand-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5M4 19h16"/><path d="m7 15 3-4 3 2 4-6"/></svg></div>
      <div><strong>Analítica Local</strong><small>Monitor automático TXT</small></div>
    </div>
    <small class="login-eyebrow">ACCESO SEGURO</small>
    <h1>Bienvenido al panel</h1>
    <p>Ingrese con su cuenta de superadministrador para revisar las mediciones y administrar el sistema.</p>
    <?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
    <form method="post" action="<?= url('login') ?>">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-row"><label for="email">Correo electrónico</label><input id="email" name="email" type="email" value="admin@quasar.local" placeholder="usuario@empresa.cl" required autocomplete="username"></div>
      <div class="form-row"><label for="password">Contraseña</label><input id="password" name="password" type="password" placeholder="Ingrese su contraseña" required autocomplete="current-password"></div>
      <button class="btn primary" type="submit">Ingresar al dashboard</button>
    </form>
    <div class="login-status"><span class="pulse"></span><span>Sistema de lectura disponible</span></div>
  </section>
</main>
