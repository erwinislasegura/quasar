<?php

declare(strict_types=1);

if (!defined('QUASAR_ROUTED') && PHP_SAPI !== 'cli') {
    require __DIR__ . '/public/index.php';
    return;
}

require_once __DIR__ . '/app/Core/helpers.php';
require_once __DIR__ . '/app/Models/MeasurementRepository.php';

if (!function_exists('cargarMediciones')) {
    /**
     * Reads the real TXT source and exposes the same object shape expected by the
     * approved design's JavaScript. The reference HTML below remains unchanged.
     *
     * @return list<array{id:int,iso:string,fecha:string,hora:string,tiempo:float,razon:float,conductividad:float,archivo:string}>
     */
    function cargarMediciones(string $archivo): array
    {
        $mediciones = [];
        $lineas = is_file($archivo)
            ? file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : [];

        foreach ($lineas ?: [] as $linea) {
            $campos = str_getcsv($linea, ';');
            if (count($campos) < 7) {
                continue;
            }

            if (!preg_match('/^(\\d{2})-(\\d{2})-(\\d{4})-(\\d{2}:\\d{2}:\\d{2})$/', $campos[0], $fecha)) {
                continue;
            }

            $mediciones[] = [
                'id' => count($mediciones) + 1,
                'iso' => sprintf('%s-%s-%sT%s', $fecha[3], $fecha[2], $fecha[1], $fecha[4]),
                'fecha' => sprintf('%s-%s-%s', $fecha[1], $fecha[2], $fecha[3]),
                'hora' => $fecha[4],
                'tiempo' => (float) str_replace(',', '.', $campos[2]),
                'razon' => (float) str_replace(',', '.', $campos[4]),
                'conductividad' => (float) str_replace(',', '.', $campos[6]),
                'archivo' => basename($archivo),
            ];
        }

        return $mediciones;
    }
}

$conectar = require __DIR__ . '/config/database.php';
$conexion = $conectar();
$mediciones = $conexion instanceof PDO
    ? (new App\Models\MeasurementRepository($conexion))->all()
    : cargarMediciones(__DIR__ . '/Analisis.txt');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Dashboard local para visualización automática de archivos TXT">
  <title>Panel de Análisis TXT</title>
  <style>
    :root {
      --bg: #f4f7fb;
      --panel: #ffffff;
      --panel-soft: #f8fafc;
      --sidebar: #0d1f38;
      --sidebar-2: #102a4c;
      --primary: #2368e8;
      --primary-soft: #eaf1ff;
      --cyan: #17a6b6;
      --cyan-soft: #e7f8fa;
      --amber: #e99a18;
      --amber-soft: #fff4df;
      --danger: #df4c5f;
      --danger-soft: #fff0f2;
      --success: #169b68;
      --success-soft: #e9f8f1;
      --text: #152238;
      --muted: #6c7a90;
      --border: #e4eaf2;
      --shadow: 0 14px 35px rgba(29, 53, 87, 0.08);
      --radius: 18px;
    }

    * { box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 14px;
    }

    button, input, select { font: inherit; }

    button { cursor: pointer; }

    .app {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 246px minmax(0, 1fr);
    }

    .sidebar {
      position: sticky;
      top: 0;
      height: 100vh;
      overflow: auto;
      background:
        radial-gradient(circle at 10% 0%, rgba(44, 113, 229, 0.22), transparent 33%),
        linear-gradient(180deg, var(--sidebar-2), var(--sidebar));
      color: #fff;
      padding: 24px 18px;
      z-index: 20;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0 8px 24px;
      border-bottom: 1px solid rgba(255,255,255,.11);
    }

    .brand-mark {
      width: 42px;
      height: 42px;
      border-radius: 13px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, #2b75ed, #19b3c4);
      box-shadow: 0 10px 24px rgba(24, 150, 202, .28);
    }

    .brand-mark svg { width: 24px; height: 24px; }

    .brand strong { display: block; font-size: 15px; letter-spacing: .2px; }
    .brand small { color: rgba(255,255,255,.6); font-size: 11px; }

    .nav-label {
      margin: 24px 12px 8px;
      color: rgba(255,255,255,.42);
      font-size: 10px;
      font-weight: 800;
      letter-spacing: 1.4px;
      text-transform: uppercase;
    }

    .nav {
      display: grid;
      gap: 5px;
    }

    .nav a {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 11px 13px;
      color: rgba(255,255,255,.72);
      text-decoration: none;
      border-radius: 11px;
      transition: .2s ease;
    }

    .nav a:hover,
    .nav a.active {
      background: rgba(255,255,255,.105);
      color: #fff;
    }

    .nav a.active {
      box-shadow: inset 3px 0 0 #4e91ff;
    }

    .nav svg { width: 18px; height: 18px; flex: 0 0 auto; }

    .source-card {
      margin-top: 24px;
      padding: 15px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.06);
      border-radius: 14px;
    }

    .source-card .status {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .pulse {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: #48dfa3;
      box-shadow: 0 0 0 5px rgba(72,223,163,.12);
    }

    .source-card code {
      display: block;
      padding: 9px 10px;
      border-radius: 9px;
      background: rgba(0,0,0,.17);
      color: rgba(255,255,255,.72);
      word-break: break-all;
      font-size: 10px;
      line-height: 1.45;
    }

    .main {
      min-width: 0;
      padding: 0 28px 34px;
    }

    .topbar {
      min-height: 76px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      position: sticky;
      top: 0;
      z-index: 15;
      background: rgba(244,247,251,.88);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(228,234,242,.7);
    }

    .menu-button {
      display: none;
      border: 0;
      background: var(--panel);
      width: 42px;
      height: 42px;
      border-radius: 12px;
      color: var(--text);
      box-shadow: var(--shadow);
    }

    .page-title h1 {
      margin: 0;
      font-size: 21px;
      letter-spacing: -.45px;
    }

    .page-title p {
      margin: 4px 0 0;
      color: var(--muted);
      font-size: 12px;
    }

    .top-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .last-update {
      color: var(--muted);
      font-size: 11px;
      text-align: right;
    }

    .btn {
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      min-height: 40px;
      border-radius: 11px;
      padding: 0 14px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-weight: 750;
      transition: .2s ease;
      box-shadow: 0 5px 18px rgba(29,53,87,.05);
    }

    .btn:hover { transform: translateY(-1px); border-color: #ccd6e4; }
    .btn.primary { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn svg { width: 17px; height: 17px; }

    .content { padding-top: 24px; }

    .hero-strip {
      background:
        linear-gradient(100deg, rgba(14,39,73,.98), rgba(25,74,127,.94)),
        radial-gradient(circle at 90% 30%, rgba(45,129,255,.45), transparent 45%);
      color: #fff;
      padding: 23px 25px;
      border-radius: var(--radius);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      box-shadow: var(--shadow);
      overflow: hidden;
      position: relative;
    }

    .hero-strip::after {
      content: "";
      position: absolute;
      width: 280px;
      height: 280px;
      right: -85px;
      top: -130px;
      border-radius: 50%;
      border: 40px solid rgba(255,255,255,.045);
    }

    .hero-copy { position: relative; z-index: 1; }
    .hero-copy small { color: #9dc3ff; font-weight: 800; letter-spacing: .7px; text-transform: uppercase; }
    .hero-copy h2 { margin: 7px 0 5px; font-size: 21px; letter-spacing: -.3px; }
    .hero-copy p { margin: 0; color: rgba(255,255,255,.68); max-width: 650px; font-size: 12px; line-height: 1.6; }
    .hero-file { position: relative; z-index: 1; text-align: right; white-space: nowrap; }
    .hero-file strong { display: block; font-size: 13px; }
    .hero-file span { color: rgba(255,255,255,.58); font-size: 11px; }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0,1fr));
      gap: 16px;
      margin-top: 18px;
    }

    .kpi {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 18px;
      box-shadow: var(--shadow);
      display: grid;
      grid-template-columns: 46px 1fr;
      gap: 13px;
      align-items: center;
    }

    .kpi-icon {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      display: grid;
      place-items: center;
    }

    .kpi-icon svg { width: 22px; height: 22px; }
    .kpi-icon.blue { color: var(--primary); background: var(--primary-soft); }
    .kpi-icon.cyan { color: var(--cyan); background: var(--cyan-soft); }
    .kpi-icon.amber { color: var(--amber); background: var(--amber-soft); }
    .kpi-icon.red { color: var(--danger); background: var(--danger-soft); }

    .kpi-label { color: var(--muted); font-size: 11px; font-weight: 700; }
    .kpi-value { margin-top: 3px; font-size: 22px; font-weight: 850; letter-spacing: -.5px; }
    .kpi-foot { margin-top: 3px; color: #8b97a8; font-size: 10px; }

    .dashboard-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.6fr) minmax(300px, .8fr);
      gap: 16px;
      margin-top: 16px;
    }

    .panel {
      min-width: 0;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
    }

    .panel-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 14px;
      padding: 18px 20px 13px;
    }

    .panel-title h3 {
      margin: 0;
      font-size: 14px;
      letter-spacing: -.15px;
    }

    .panel-title p {
      margin: 5px 0 0;
      color: var(--muted);
      font-size: 10px;
    }

    .legend {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-end;
      gap: 12px;
      color: var(--muted);
      font-size: 10px;
    }

    .legend-item { display: flex; align-items: center; gap: 6px; }
    .legend-dot { width: 8px; height: 8px; border-radius: 50%; }

    .chart-wrap {
      padding: 0 14px 16px;
      height: 300px;
      position: relative;
    }

    canvas { width: 100%; height: 100%; display: block; }

    .quality-body { padding: 2px 20px 20px; }

    .quality-chart {
      width: 178px;
      height: 178px;
      border-radius: 50%;
      margin: 8px auto 20px;
      display: grid;
      place-items: center;
      position: relative;
      background: conic-gradient(var(--success) 0 var(--valid-angle), #e8edf4 var(--valid-angle) 360deg);
    }

    .quality-chart::after {
      content: "";
      position: absolute;
      inset: 18px;
      background: var(--panel);
      border-radius: 50%;
    }

    .quality-value { position: relative; z-index: 1; text-align: center; }
    .quality-value strong { display: block; font-size: 30px; }
    .quality-value span { color: var(--muted); font-size: 10px; }

    .quality-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 11px 0;
      border-top: 1px solid var(--border);
    }

    .quality-row span { color: var(--muted); font-size: 11px; }
    .quality-row strong { font-size: 12px; }

    .range-grid {
      margin-top: 16px;
      display: grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap: 16px;
    }

    .range-card {
      padding: 17px 18px;
      display: grid;
      gap: 13px;
    }

    .range-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .range-head strong { font-size: 12px; }
    .range-head span { color: var(--muted); font-size: 10px; }
    .range-values { display: flex; justify-content: space-between; gap: 10px; }
    .range-values div { min-width: 0; }
    .range-values small { display: block; color: var(--muted); font-size: 9px; text-transform: uppercase; font-weight: 800; letter-spacing: .7px; }
    .range-values b { display: block; margin-top: 4px; font-size: 16px; }
    .bar { height: 7px; border-radius: 99px; background: #eef2f7; overflow: hidden; }
    .bar > i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--primary), #5f9bff); }

    .table-panel { margin-top: 16px; overflow: hidden; }

    .table-tools {
      padding: 0 20px 16px;
      display: grid;
      grid-template-columns: minmax(210px, 1fr) repeat(3, minmax(130px, .42fr));
      gap: 10px;
    }

    .field {
      position: relative;
      min-width: 0;
    }

    .field svg {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      color: #8b98aa;
      pointer-events: none;
    }

    .field input,
    .field select {
      width: 100%;
      height: 41px;
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 0 12px;
      background: var(--panel-soft);
      color: var(--text);
      outline: none;
    }

    .field.has-icon input { padding-left: 38px; }
    .field input:focus, .field select:focus { border-color: #9dbcf5; box-shadow: 0 0 0 3px rgba(35,104,232,.08); }

    .table-scroll { overflow: auto; border-top: 1px solid var(--border); }

    table {
      width: 100%;
      min-width: 850px;
      border-collapse: collapse;
    }

    th, td {
      padding: 13px 18px;
      text-align: left;
      border-bottom: 1px solid #edf1f6;
      white-space: nowrap;
    }

    th {
      background: #f8fafc;
      color: #78869b;
      font-size: 9px;
      font-weight: 850;
      letter-spacing: .75px;
      text-transform: uppercase;
      position: sticky;
      top: 0;
      z-index: 1;
    }

    th.sortable { cursor: pointer; user-select: none; }
    th.sortable:hover { color: var(--primary); }
    td { font-size: 11px; }
    tbody tr:hover { background: #fbfdff; }
    .row-index { color: #97a3b4; width: 55px; }
    .metric { font-variant-numeric: tabular-nums; font-weight: 700; }
    .date-cell strong { display: block; font-size: 11px; }
    .date-cell span { display: block; margin-top: 3px; color: var(--muted); font-size: 9px; }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 9px;
      border-radius: 99px;
      font-size: 9px;
      font-weight: 800;
    }

    .badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; }
    .badge.ok { color: var(--success); background: var(--success-soft); }
    .badge.ok::before { background: var(--success); }
    .badge.warn { color: #bb7210; background: var(--amber-soft); }
    .badge.warn::before { background: var(--amber); }
    .badge.error { color: var(--danger); background: var(--danger-soft); }
    .badge.error::before { background: var(--danger); }

    .table-footer {
      min-height: 62px;
      padding: 12px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 15px;
      color: var(--muted);
      font-size: 10px;
    }

    .pagination { display: flex; align-items: center; gap: 5px; }
    .page-btn {
      min-width: 32px;
      height: 32px;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: var(--panel);
      color: var(--text);
      font-size: 10px;
      font-weight: 750;
    }

    .page-btn.active { background: var(--primary); border-color: var(--primary); color: #fff; }
    .page-btn:disabled { opacity: .4; cursor: not-allowed; }

    .empty {
      padding: 44px 20px;
      text-align: center;
      color: var(--muted);
    }

    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(7,18,35,.45);
      z-index: 18;
    }

    .tooltip {
      position: fixed;
      pointer-events: none;
      background: #102039;
      color: #fff;
      border-radius: 9px;
      padding: 8px 10px;
      font-size: 10px;
      box-shadow: 0 8px 24px rgba(0,0,0,.25);
      opacity: 0;
      transform: translate(-50%, calc(-100% - 12px));
      transition: opacity .12s ease;
      z-index: 99;
      white-space: nowrap;
    }

    @media (max-width: 1180px) {
      .kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
      .table-tools { grid-template-columns: repeat(2, minmax(0,1fr)); }
    }

    @media (max-width: 930px) {
      .app { grid-template-columns: 1fr; }
      .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        transform: translateX(-104%);
        width: 246px;
        transition: transform .25s ease;
      }
      body.sidebar-open .sidebar { transform: translateX(0); }
      body.sidebar-open .overlay { display: block; }
      .menu-button { display: grid; place-items: center; }
      .main { padding: 0 18px 28px; }
      .topbar { justify-content: flex-start; }
      .top-actions { margin-left: auto; }
      .dashboard-grid { grid-template-columns: 1fr; }
      .quality-body {
        display: grid;
        grid-template-columns: 210px 1fr;
        align-items: center;
        gap: 20px;
      }
      .quality-chart { margin: 5px auto; }
    }

    @media (max-width: 680px) {
      .main { padding-inline: 12px; }
      .topbar { min-height: 68px; }
      .page-title h1 { font-size: 17px; }
      .page-title p, .last-update { display: none; }
      .top-actions .btn span { display: none; }
      .hero-strip { align-items: flex-start; padding: 19px; }
      .hero-file { display: none; }
      .hero-copy h2 { font-size: 17px; }
      .kpi-grid { grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
      .kpi { padding: 14px 12px; grid-template-columns: 38px 1fr; gap: 10px; }
      .kpi-icon { width: 38px; height: 38px; border-radius: 11px; }
      .kpi-value { font-size: 17px; }
      .range-grid { grid-template-columns: 1fr; gap: 10px; }
      .quality-body { display: block; }
      .table-tools { grid-template-columns: 1fr; }
      .panel-head { padding-inline: 15px; }
      .legend { display: none; }
      .chart-wrap { height: 245px; padding-inline: 6px; }
      .table-tools { padding-inline: 15px; }
      .table-footer { align-items: flex-start; flex-direction: column; }
    }
  </style>
</head>
<body>
  <div class="overlay" id="overlay"></div>
  <div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19V5M4 19h16"/>
          <path d="m7 15 3-4 3 2 4-6"/>
          <circle cx="7" cy="15" r="1"/><circle cx="10" cy="11" r="1"/>
          <circle cx="13" cy="13" r="1"/><circle cx="17" cy="7" r="1"/>
        </svg>
      </div>
      <div>
        <strong>Analítica Local</strong>
        <small>Monitor automático TXT</small>
      </div>
    </div>

    <div class="nav-label">Principal</div>
    <nav class="nav">
      <a href="#resumen" class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/>
          <rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>
        </svg>
        Dashboard
      </a>
      <a href="#registros">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 6h16M4 12h16M4 18h16"/><circle cx="7" cy="6" r=".5" fill="currentColor"/>
        </svg>
        Registros
      </a>
      <a href="#graficos">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
        </svg>
        Tendencias
      </a>
    </nav>

    <div class="nav-label">Gestión</div>
    <nav class="nav">
      <a href="<?= url('mediciones') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16"/></svg>Mediciones</a>
      <a href="<?= url('archivos') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h8M8 12h8"/></svg>Archivos</a>
      <a href="<?= url('equipos') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="12"/><path d="M8 21h8M12 17v4"/></svg>Equipos</a>
      <a href="<?= url('windows-reader') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5.5 11 4v7H4ZM13 3.6 20 2v9h-7ZM4 13h7v7l-7-1.5ZM13 13h7v9l-7-1.6Z"/></svg>Lector Windows</a>
    </nav>

    <div class="nav-label">Administración</div>
    <nav class="nav">
      <a href="<?= url('usuarios') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 8h5M19.5 5.5v5"/></svg>Usuarios</a>
      <a href="<?= url('roles') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v5c0 5 3.4 8 8 9 4.6-1 8-4 8-9V7Z"/></svg>Roles</a>
      <a href="<?= url('permisos') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="12" r="4"/><path d="m12 12 9-9M17 3h4v4"/></svg>Permisos</a>
      <a href="<?= url('auditoria') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 3h14v18H5zM8 8h8M8 12h8M8 16h5"/></svg>Auditoría</a>
    </nav>

    <div class="nav-label">Sistema</div>
    <nav class="nav">
      <a href="#fuente">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/>
        </svg>
        Archivo fuente
      </a>
      <a href="<?= url('errores') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 2.5 20h19ZM12 9v5M12 17h.01"/></svg>Errores</a>
      <a href="<?= url('configuracion') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.17.38.4.73.71 1 .3.27.7.4 1.1.4h.1v4h-.1a1.7 1.7 0 0 0-1.81.6Z"/>
        </svg>
        Configuración
      </a>
      <a href="<?= url('logout') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h10"/></svg>Cerrar sesión</a>
    </nav>

    <div class="source-card" id="fuente">
      <div class="status"><span class="pulse"></span> Lectura activa</div>
      <code>C:\SistemaTXT\Entrada\Analisis.txt</code>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <button class="menu-button" id="menuButton" aria-label="Abrir menú">
        <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
      <div class="page-title">
        <h1>Panel de mediciones</h1>
        <p>Información procesada automáticamente desde el archivo local</p>
      </div>
      <div class="top-actions">
        <div class="last-update">
          Última lectura<br><strong id="lastReadText">—</strong>
        </div>
        <a class="btn" href="<?= url('windows-reader') ?>" title="Abrir lector visual en Windows">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M4 5.5 11 4v7H4V5.5ZM13 3.6 20 2v9h-7V3.6ZM4 13h7v7l-7-1.5V13ZM13 13h7v9l-7-1.6V13Z"/>
          </svg>
          <span>Abrir lector</span>
        </a>
        <button class="btn" id="exportButton">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M5 19h14"/>
          </svg>
          <span>Exportar CSV</span>
        </button>
        <button class="btn primary" id="refreshButton">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M20 7v5h-5"/><path d="M4 17v-5h5"/><path d="M6.1 9A7 7 0 0 1 18.7 6.3L20 12M4 12l1.3 5.7A7 7 0 0 0 17.9 15"/>
          </svg>
          <span>Actualizar</span>
        </button>
      </div>
    </header>

    <div class="content">
      <section class="hero-strip" id="resumen">
        <div class="hero-copy">
          <small>Sistema operativo</small>
          <h2>Lectura y análisis automático de variables</h2>
          <p>Visualización de Fecha, Hora, Tiempo, Razón y Conductividad, con detección de valores negativos y filtros para revisar cada medición.</p>
        </div>
        <div class="hero-file">
          <strong>Analisis.txt</strong>
          <span id="heroFileMeta">—</span>
        </div>
      </section>

      <section class="kpi-grid">
        <article class="kpi">
          <div class="kpi-icon blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h5"/>
            </svg>
          </div>
          <div>
            <div class="kpi-label">Total de registros</div>
            <div class="kpi-value" id="totalRecords">0</div>
            <div class="kpi-foot" id="dateCount">0 fechas diferentes</div>
          </div>
        </article>

        <article class="kpi">
          <div class="kpi-icon cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/>
            </svg>
          </div>
          <div>
            <div class="kpi-label">Tiempo promedio</div>
            <div class="kpi-value" id="avgTiempo">0</div>
            <div class="kpi-foot">Promedio general del archivo</div>
          </div>
        </article>

        <article class="kpi">
          <div class="kpi-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M4 17 10 7l4 6 3-5 3 9"/><path d="M3 20h18"/>
            </svg>
          </div>
          <div>
            <div class="kpi-label">Razón promedio</div>
            <div class="kpi-value" id="avgRazon">0</div>
            <div class="kpi-foot">Incluye todos los valores</div>
          </div>
        </article>

        <article class="kpi">
          <div class="kpi-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5M12 17h.01"/>
            </svg>
          </div>
          <div>
            <div class="kpi-label">Conductividad negativa</div>
            <div class="kpi-value" id="negativeCount">0</div>
            <div class="kpi-foot">Registros que requieren revisión</div>
          </div>
        </article>
      </section>

      <section class="dashboard-grid" id="graficos">
        <article class="panel">
          <div class="panel-head">
            <div class="panel-title">
              <h3>Evolución de las mediciones</h3>
              <p>Serie cronológica de Tiempo y Conductividad</p>
            </div>
            <div class="legend">
              <span class="legend-item"><i class="legend-dot" style="background:#2368e8"></i>Tiempo</span>
              <span class="legend-item"><i class="legend-dot" style="background:#17a6b6"></i>Conductividad</span>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="mainChart"></canvas>
          </div>
        </article>

        <article class="panel">
          <div class="panel-head">
            <div class="panel-title">
              <h3>Calidad de conductividad</h3>
              <p>Valores no negativos frente a valores negativos</p>
            </div>
          </div>
          <div class="quality-body">
            <div class="quality-chart" id="qualityChart">
              <div class="quality-value">
                <strong id="validPercent">0%</strong>
                <span>no negativos</span>
              </div>
            </div>
            <div>
              <div class="quality-row">
                <span>Valores no negativos</span>
                <strong id="validCount">0</strong>
              </div>
              <div class="quality-row">
                <span>Valores negativos</span>
                <strong id="invalidCount">0</strong>
              </div>
              <div class="quality-row">
                <span>Promedio no negativo</span>
                <strong id="avgConductividad">0</strong>
              </div>
            </div>
          </div>
        </article>
      </section>

      <section class="range-grid">
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de Tiempo</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minTiempo">0</b></div><div style="text-align:right"><small>Máximo</small><b id="maxTiempo">0</b></div></div>
          <div class="bar"><i style="width:84%"></i></div>
        </article>
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de Razón</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minRazon">0</b></div><div style="text-align:right"><small>Máximo</small><b id="maxRazon">0</b></div></div>
          <div class="bar"><i style="width:72%; background:linear-gradient(90deg,#e99a18,#ffbd50)"></i></div>
        </article>
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de Conductividad</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minConductividad">0</b></div><div style="text-align:right"><small>Máximo</small><b id="maxConductividad">0</b></div></div>
          <div class="bar"><i style="width:62%; background:linear-gradient(90deg,#17a6b6,#48c9d5)"></i></div>
        </article>
      </section>

      <section class="panel table-panel" id="registros">
        <div class="panel-head">
          <div class="panel-title">
            <h3>Detalle de registros</h3>
            <p>Consulta, ordena y filtra todas las líneas procesadas</p>
          </div>
          <span class="badge ok" id="visibleBadge">0 visibles</span>
        </div>

        <div class="table-tools">
          <label class="field has-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            <input type="search" id="searchInput" placeholder="Buscar fecha, hora o valor...">
          </label>
          <label class="field">
            <select id="dateFilter"><option value="">Todas las fechas</option></select>
          </label>
          <label class="field">
            <select id="statusFilter">
              <option value="">Toda conductividad</option>
              <option value="valid">No negativa</option>
              <option value="negative">Negativa</option>
              <option value="zero">Igual a cero</option>
            </select>
          </label>
          <label class="field">
            <select id="pageSize">
              <option value="10">10 por página</option>
              <option value="20">20 por página</option>
              <option value="50">50 por página</option>
              <option value="100">100 por página</option>
            </select>
          </label>
        </div>

        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th class="sortable" data-sort="iso">Fecha y hora ↕</th>
                <th class="sortable" data-sort="tiempo">Tiempo ↕</th>
                <th class="sortable" data-sort="razon">Razón ↕</th>
                <th class="sortable" data-sort="conductividad">Conductividad ↕</th>
                <th>Estado</th>
                <th>Archivo</th>
              </tr>
            </thead>
            <tbody id="tableBody"></tbody>
          </table>
          <div class="empty" id="emptyState" hidden>No se encontraron registros con los filtros seleccionados.</div>
        </div>

        <div class="table-footer">
          <span id="tableInfo">Mostrando 0 registros</span>
          <div class="pagination" id="pagination"></div>
        </div>
      </section>
    </div>
  </main>
  </div>

  <div class="tooltip" id="tooltip"></div>

  <script>
    const RAW_DATA = <?= json_encode($mediciones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const fmt = (value, decimals = 2) =>
      new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(value);

    const state = {
      filtered: [...RAW_DATA],
      page: 1,
      pageSize: 10,
      sortKey: 'iso',
      sortDirection: 'desc'
    };

    const els = {
      totalRecords: document.getElementById('totalRecords'),
      dateCount: document.getElementById('dateCount'),
      avgTiempo: document.getElementById('avgTiempo'),
      avgRazon: document.getElementById('avgRazon'),
      negativeCount: document.getElementById('negativeCount'),
      validPercent: document.getElementById('validPercent'),
      validCount: document.getElementById('validCount'),
      invalidCount: document.getElementById('invalidCount'),
      avgConductividad: document.getElementById('avgConductividad'),
      qualityChart: document.getElementById('qualityChart'),
      minTiempo: document.getElementById('minTiempo'),
      maxTiempo: document.getElementById('maxTiempo'),
      minRazon: document.getElementById('minRazon'),
      maxRazon: document.getElementById('maxRazon'),
      minConductividad: document.getElementById('minConductividad'),
      maxConductividad: document.getElementById('maxConductividad'),
      tableBody: document.getElementById('tableBody'),
      emptyState: document.getElementById('emptyState'),
      tableInfo: document.getElementById('tableInfo'),
      pagination: document.getElementById('pagination'),
      visibleBadge: document.getElementById('visibleBadge'),
      searchInput: document.getElementById('searchInput'),
      dateFilter: document.getElementById('dateFilter'),
      statusFilter: document.getElementById('statusFilter'),
      pageSize: document.getElementById('pageSize'),
      lastReadText: document.getElementById('lastReadText'),
      heroFileMeta: document.getElementById('heroFileMeta'),
      tooltip: document.getElementById('tooltip')
    };

    function average(items, key) {
      return items.length ? items.reduce((sum, item) => sum + item[key], 0) / items.length : 0;
    }

    function initStats() {
      const validConductivity = RAW_DATA.filter(item => item.conductividad >= 0);
      const invalidConductivity = RAW_DATA.filter(item => item.conductividad < 0);
      const uniqueDates = [...new Set(RAW_DATA.map(item => item.fecha))];

      if (!RAW_DATA.length) {
        els.totalRecords.textContent = '0';
        els.dateCount.textContent = '0 fechas diferentes';
        els.validPercent.textContent = '0%';
        els.heroFileMeta.textContent = 'Sin líneas procesadas';
        els.lastReadText.textContent = 'Sin datos';
        els.qualityChart.style.setProperty('--valid-angle', '0deg');
        return;
      }

      els.totalRecords.textContent = RAW_DATA.length;
      els.dateCount.textContent = `${uniqueDates.length} fechas diferentes`;
      els.avgTiempo.textContent = fmt(average(RAW_DATA, 'tiempo'), 2);
      els.avgRazon.textContent = fmt(average(RAW_DATA, 'razon'), 3);
      els.negativeCount.textContent = invalidConductivity.length;

      const validPercentage = Math.round((validConductivity.length / RAW_DATA.length) * 100);
      els.validPercent.textContent = `${validPercentage}%`;
      els.validCount.textContent = validConductivity.length;
      els.invalidCount.textContent = invalidConductivity.length;
      els.avgConductividad.textContent = fmt(average(validConductivity, 'conductividad'), 2);
      els.qualityChart.style.setProperty('--valid-angle', `${validPercentage * 3.6}deg`);

      const tiempoValues = RAW_DATA.map(x => x.tiempo);
      const razonValues = RAW_DATA.map(x => x.razon);
      const conductivityValues = RAW_DATA.map(x => x.conductividad);

      els.minTiempo.textContent = fmt(Math.min(...tiempoValues), 2);
      els.maxTiempo.textContent = fmt(Math.max(...tiempoValues), 2);
      els.minRazon.textContent = fmt(Math.min(...razonValues), 3);
      els.maxRazon.textContent = fmt(Math.max(...razonValues), 3);
      els.minConductividad.textContent = fmt(Math.min(...conductivityValues), 0);
      els.maxConductividad.textContent = fmt(Math.max(...conductivityValues), 0);

      const last = [...RAW_DATA].sort((a,b) => a.iso.localeCompare(b.iso)).at(-1);
      els.lastReadText.textContent = `${last.fecha} · ${last.hora}`;
      els.heroFileMeta.textContent = `${RAW_DATA.length} líneas procesadas`;

      uniqueDates
        .sort((a,b) => {
          const parse = value => value.split('-').reverse().join('-');
          return parse(b).localeCompare(parse(a));
        })
        .forEach(date => {
          const option = document.createElement('option');
          option.value = date;
          option.textContent = date;
          els.dateFilter.appendChild(option);
        });
    }

    function getStatus(item) {
      if (item.conductividad < 0) return {label: 'Valor negativo', className: 'error'};
      if (item.conductividad === 0) return {label: 'Valor cero', className: 'warn'};
      return {label: 'No negativo', className: 'ok'};
    }

    function applyFilters() {
      const query = els.searchInput.value.trim().toLowerCase();
      const date = els.dateFilter.value;
      const status = els.statusFilter.value;

      state.filtered = RAW_DATA.filter(item => {
        const haystack = [
          item.fecha,
          item.hora,
          item.tiempo,
          item.razon,
          item.conductividad,
          item.archivo
        ].join(' ').toLowerCase();

        const matchesQuery = !query || haystack.includes(query);
        const matchesDate = !date || item.fecha === date;

        let matchesStatus = true;
        if (status === 'valid') matchesStatus = item.conductividad >= 0;
        if (status === 'negative') matchesStatus = item.conductividad < 0;
        if (status === 'zero') matchesStatus = item.conductividad === 0;

        return matchesQuery && matchesDate && matchesStatus;
      });

      state.page = 1;
      renderTable();
    }

    function sortData(data) {
      return [...data].sort((a,b) => {
        const av = a[state.sortKey];
        const bv = b[state.sortKey];
        const direction = state.sortDirection === 'asc' ? 1 : -1;
        if (typeof av === 'number') return (av - bv) * direction;
        return String(av).localeCompare(String(bv)) * direction;
      });
    }

    function renderTable() {
      const sorted = sortData(state.filtered);
      const totalPages = Math.max(1, Math.ceil(sorted.length / state.pageSize));
      if (state.page > totalPages) state.page = totalPages;
      const start = (state.page - 1) * state.pageSize;
      const rows = sorted.slice(start, start + state.pageSize);

      els.tableBody.innerHTML = rows.map((item, index) => {
        const status = getStatus(item);
        return `
          <tr>
            <td class="row-index">${start + index + 1}</td>
            <td class="date-cell"><strong>${item.fecha}</strong><span>${item.hora}</span></td>
            <td class="metric">${fmt(item.tiempo, 3)}</td>
            <td class="metric">${fmt(item.razon, 6)}</td>
            <td class="metric">${fmt(item.conductividad, 2)}</td>
            <td><span class="badge ${status.className}">${status.label}</span></td>
            <td>${item.archivo}</td>
          </tr>
        `;
      }).join('');

      els.emptyState.hidden = rows.length > 0;
      els.tableInfo.textContent = sorted.length
        ? `Mostrando ${start + 1}–${Math.min(start + state.pageSize, sorted.length)} de ${sorted.length} registros`
        : 'Mostrando 0 registros';
      els.visibleBadge.textContent = `${sorted.length} visibles`;

      renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
      const buttons = [];
      buttons.push(`<button class="page-btn" data-page="${state.page - 1}" ${state.page === 1 ? 'disabled' : ''}>‹</button>`);

      const candidates = new Set([1, totalPages, state.page - 1, state.page, state.page + 1]);
      [...candidates]
        .filter(page => page >= 1 && page <= totalPages)
        .sort((a,b) => a-b)
        .forEach((page, index, arr) => {
          if (index > 0 && page - arr[index - 1] > 1) {
            buttons.push(`<span style="padding:0 3px">…</span>`);
          }
          buttons.push(`<button class="page-btn ${page === state.page ? 'active' : ''}" data-page="${page}">${page}</button>`);
        });

      buttons.push(`<button class="page-btn" data-page="${state.page + 1}" ${state.page === totalPages ? 'disabled' : ''}>›</button>`);
      els.pagination.innerHTML = buttons.join('');
    }

    function setupTableEvents() {
      [els.searchInput, els.dateFilter, els.statusFilter].forEach(el => {
        el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
      });

      els.pageSize.addEventListener('change', () => {
        state.pageSize = Number(els.pageSize.value);
        state.page = 1;
        renderTable();
      });

      els.pagination.addEventListener('click', event => {
        const button = event.target.closest('[data-page]');
        if (!button || button.disabled) return;
        state.page = Number(button.dataset.page);
        renderTable();
      });

      document.querySelectorAll('th.sortable').forEach(header => {
        header.addEventListener('click', () => {
          const key = header.dataset.sort;
          if (state.sortKey === key) {
            state.sortDirection = state.sortDirection === 'asc' ? 'desc' : 'asc';
          } else {
            state.sortKey = key;
            state.sortDirection = 'asc';
          }
          renderTable();
        });
      });
    }

    function drawChart() {
      const canvas = document.getElementById('mainChart');
      const rect = canvas.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      canvas.width = Math.max(1, Math.floor(rect.width * dpr));
      canvas.height = Math.max(1, Math.floor(rect.height * dpr));

      const ctx = canvas.getContext('2d');
      ctx.scale(dpr, dpr);

      if (!RAW_DATA.length) {
        ctx.clearRect(0, 0, rect.width, rect.height);
        ctx.fillStyle = '#8a96a8';
        ctx.font = '11px system-ui';
        ctx.textAlign = 'center';
        ctx.fillText('Sin mediciones disponibles', rect.width / 2, rect.height / 2);
        return;
      }

      const width = rect.width;
      const height = rect.height;
      const pad = {top: 15, right: 24, bottom: 34, left: 45};
      const plotW = width - pad.left - pad.right;
      const plotH = height - pad.top - pad.bottom;

      ctx.clearRect(0,0,width,height);
      ctx.font = '10px system-ui';
      ctx.textBaseline = 'middle';

      const data = RAW_DATA;
      const tiempo = data.map(d => d.tiempo);
      const cond = data.map(d => d.conductividad);
      const maxY = Math.ceil(Math.max(...tiempo, ...cond) / 50) * 50;
      const minY = Math.min(0, Math.floor(Math.min(...tiempo, ...cond) / 10) * 10);

      const x = index => pad.left + (index / Math.max(1, data.length - 1)) * plotW;
      const y = value => pad.top + (1 - (value - minY) / (maxY - minY)) * plotH;

      ctx.strokeStyle = '#e8edf4';
      ctx.lineWidth = 1;
      ctx.fillStyle = '#8a96a8';
      ctx.textAlign = 'right';

      const gridCount = 5;
      for (let i=0; i<=gridCount; i++) {
        const value = minY + ((maxY - minY) / gridCount) * i;
        const py = y(value);
        ctx.beginPath();
        ctx.moveTo(pad.left, py);
        ctx.lineTo(width - pad.right, py);
        ctx.stroke();
        ctx.fillText(fmt(value,0), pad.left - 8, py);
      }

      ctx.textAlign = 'center';
      const labelCount = width < 520 ? 4 : 6;
      for (let i=0; i<labelCount; i++) {
        const index = Math.round((i/(labelCount-1)) * (data.length-1));
        const px = x(index);
        const label = data[index].fecha.slice(0,5);
        ctx.fillText(label, px, height - 14);
      }

      const drawSeries = (key, color) => {
        ctx.beginPath();
        data.forEach((item,index) => {
          const px = x(index);
          const py = y(item[key]);
          if (index === 0) ctx.moveTo(px,py);
          else ctx.lineTo(px,py);
        });
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.stroke();
      };

      drawSeries('tiempo', '#2368e8');
      drawSeries('conductividad', '#17a6b6');

      canvas._chartMeta = {x,y,pad,plotW,plotH,data};
    }

    function setupChartTooltip() {
      const canvas = document.getElementById('mainChart');

      canvas.addEventListener('mousemove', event => {
        const meta = canvas._chartMeta;
        if (!meta) return;

        const rect = canvas.getBoundingClientRect();
        const mx = event.clientX - rect.left;
        const relative = Math.min(1, Math.max(0, (mx - meta.pad.left) / meta.plotW));
        const index = Math.round(relative * (meta.data.length - 1));
        const item = meta.data[index];

        els.tooltip.innerHTML = `
          <strong>${item.fecha} ${item.hora}</strong><br>
          Tiempo: ${fmt(item.tiempo,3)} · Conductividad: ${fmt(item.conductividad,2)}
        `;
        els.tooltip.style.left = `${event.clientX}px`;
        els.tooltip.style.top = `${event.clientY}px`;
        els.tooltip.style.opacity = '1';
      });

      canvas.addEventListener('mouseleave', () => {
        els.tooltip.style.opacity = '0';
      });
    }

    function exportCSV() {
      const data = sortData(state.filtered);
      const header = ['Fecha','Hora','Tiempo','Razon','Conductividad','Estado','Archivo'];
      const rows = data.map(item => [
        item.fecha,
        item.hora,
        String(item.tiempo).replace('.',','),
        String(item.razon).replace('.',','),
        String(item.conductividad).replace('.',','),
        getStatus(item).label,
        item.archivo
      ]);

      const csv = [header, ...rows]
        .map(row => row.map(value => `"${String(value).replaceAll('"','""')}"`).join(';'))
        .join('\n');

      const blob = new Blob(['\ufeff' + csv], {type: 'text/csv;charset=utf-8'});
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'registros_analisis.csv';
      link.click();
      URL.revokeObjectURL(link.href);
    }

    function setupActions() {
      document.getElementById('exportButton').addEventListener('click', exportCSV);

      document.getElementById('refreshButton').addEventListener('click', event => {
        const button = event.currentTarget;
        button.disabled = true;
        button.style.opacity = '.7';
        setTimeout(() => {
          const now = new Date();
          els.lastReadText.textContent = now.toLocaleDateString('es-CL') + ' · ' + now.toLocaleTimeString('es-CL');
          button.disabled = false;
          button.style.opacity = '1';
        }, 450);
      });

      const menuButton = document.getElementById('menuButton');
      const overlay = document.getElementById('overlay');
      menuButton.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
      overlay.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
      document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
      });
    }

    initStats();
    setupTableEvents();
    setupChartTooltip();
    setupActions();
    renderTable();
    drawChart();

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(drawChart, 120);
    });
  </script>
</body>
</html>
