<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Plataforma local de análisis industrial">
  <meta name="theme-color" content="#102a4c">
  <link rel="manifest" href="<?= url('manifest.webmanifest') ?>">
  <title><?= e($title ?? 'Quasar') ?> · Analítica Local</title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=20260807-1">
  <link rel="stylesheet" href="<?= url('assets/css/responsive.css') ?>?v=20260802-2">
</head>
<body>
  <div class="overlay" id="overlay"></div>
  <div class="app">
    <?php require dirname(__DIR__) . '/partials/sidebar.php'; ?>
    <main class="main">
      <?php require dirname(__DIR__) . '/partials/header.php'; ?>
      <div class="content">
        <?php require dirname(__DIR__) . '/partials/alerts.php'; ?>
        <?= $content ?>
        <?php require dirname(__DIR__) . '/partials/footer.php'; ?>
      </div>
    </main>
  </div>
  <div class="tooltip" id="tooltip"></div>
  <?php if (!empty($dashboardScripts)): ?>
    <script>window.QUASAR_DATA = <?= json_encode($records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
    <script src="<?= url('assets/js/dashboard.js') ?>?v=20260807-1"></script>
  <?php else: ?>
    <script src="<?= url('assets/js/admin.js') ?>?v=20260802-2"></script>
  <?php endif; ?>
</body>
</html>
