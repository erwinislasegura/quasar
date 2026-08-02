<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Plataforma local de análisis industrial">
  <title><?= e($title ?? 'Quasar') ?> · Analítica Local</title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/responsive.css') ?>">
</head>
<body>
  <div class="overlay" id="overlay"></div>
  <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
  <main class="main">
    <?php require dirname(__DIR__) . '/partials/header.php'; ?>
    <div class="content">
      <?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
      <?= $content ?>
      <?php require dirname(__DIR__) . '/partials/footer.php'; ?>
    </div>
  </main>
  <div class="tooltip" id="tooltip"></div>
  <?php if (!empty($dashboardScripts)): ?>
    <script>window.QUASAR_DATA = <?= json_encode($records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
    <script src="<?= url('assets/js/dashboard.js') ?>"></script>
  <?php else: ?>
    <script src="<?= url('assets/js/admin.js') ?>"></script>
  <?php endif; ?>
</body>
</html>
