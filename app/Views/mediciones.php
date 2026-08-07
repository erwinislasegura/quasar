<?php
$total = count($rows);
$equipmentCount = count(array_unique(array_filter(array_column($rows, 'equipoIdentificador'))));
$latest = $total ? $rows[$total - 1] : null;
$formatTsf = static fn ($value): string => number_format((float)$value, 0, ',', '.');
$formatRatio = static fn ($value): string => number_format((float)$value, 1, ',', '.');
$formatConductivity = static fn ($value): string => number_format((float)$value, 0, ',', '.');
?>
<section class="measurements-intro">
  <div>
    <small>REGISTRO DE LECTURAS</small>
    <h2>Mediciones recibidas</h2>
    <p>Consulte las lecturas reales enviadas por cada equipo y encuentre rápidamente un registro por fecha, identificador o archivo.</p>
  </div>
  <div class="measurements-live"><span></span><div><small>Fuente de datos</small><strong>Base de datos del sistema</strong></div></div>
</section>

<section class="measurement-summary" aria-label="Resumen de mediciones">
  <article><small>Total registrado</small><strong><?= number_format($total, 0, ',', '.') ?></strong><span>lecturas almacenadas</span></article>
  <article><small>Equipos con datos</small><strong><?= $equipmentCount ?></strong><span>identificadores disponibles</span></article>
  <article><small>Última medición</small><strong><?= $latest ? e($latest['fecha']) : '—' ?></strong><span><?= $latest ? e($latest['hora']) : 'Sin registros' ?></span></article>
  <article><small>Último equipo</small><strong class="summary-equipment"><?= $latest ? e($latest['equipo']) : '—' ?></strong><span><?= $latest ? e($latest['equipoIdentificador']) : 'Sin identificador' ?></span></article>
</section>

<section class="panel table-panel measurements-panel" data-measurements-table>
  <div class="panel-head measurements-head">
    <div class="panel-title"><h3>Historial de mediciones</h3><p>Los filtros se aplican sobre todos los registros disponibles.</p></div>
    <span class="measurement-counter"><strong data-measurement-visible><?= $total ?></strong><small>resultados</small></span>
  </div>

  <div class="measurement-filters">
    <label class="field measurement-search"><span>Buscar</span><input type="search" data-measurement-search placeholder="Equipo, identificador o archivo"></label>
    <label class="field"><span>Equipo</span><select data-measurement-equipment><option value="">Todos los equipos</option><?php foreach($equipment as$item):?><option value="<?= e($item['identificador']) ?>"><?= e($item['nombre']) ?> · <?= e($item['identificador']) ?> (<?= (int)$item['total'] ?>)</option><?php endforeach;?></select></label>
    <label class="field"><span>Desde</span><input type="date" data-measurement-from></label>
    <label class="field"><span>Hasta</span><input type="date" data-measurement-to></label>
    <button class="btn measurement-clear" type="button" data-measurement-clear>Limpiar filtros</button>
  </div>

  <div class="active-filter-note" data-measurement-note hidden></div>
  <div class="table-scroll">
    <table class="measurements-table">
      <thead><tr><th>Fecha y hora</th><th>Equipo</th><th>TSF (Seg)</th><th>Razón O/A</th><th>Conductividad (mS/cm)</th><th>Archivo</th></tr></thead>
      <tbody>
      <?php foreach(array_reverse($rows) as$row):?>
        <tr data-equipment="<?= e(strtolower((string)$row['equipoIdentificador'])) ?>" data-date="<?= e(substr((string)$row['iso'],0,10)) ?>">
          <td class="date-cell"><strong><?= e($row['fecha']) ?></strong><span><?= e($row['hora']) ?></span></td>
          <td class="equipment-cell"><strong><?= e($row['equipo']) ?></strong><code><?= e($row['equipoIdentificador']) ?></code></td>
          <td class="measurement-value"><strong><?= $formatTsf($row['tiempo']) ?> Seg</strong><small>TSF</small></td>
          <td class="measurement-value"><strong><?= $formatRatio($row['razon']) ?></strong><small>Razón O/A</small></td>
          <td class="measurement-value"><strong><?= $formatConductivity($row['conductividad']) ?> mS/cm</strong><small>Conductividad</small></td>
          <td><span class="file-pill"><?= e($row['archivo']) ?></span></td>
        </tr>
      <?php endforeach;?>
      </tbody>
    </table>
    <div class="empty measurement-empty" data-measurement-empty <?= $rows?'hidden':'' ?>><strong>Sin mediciones para mostrar</strong><span>Ajuste los filtros o compruebe la conexión del equipo.</span></div>
  </div>
  <div class="table-footer measurement-footer"><span data-measurement-count>Mostrando <?= min(25,$total) ?> de <?= $total ?> registros</span><div class="pagination" data-measurement-pagination></div></div>
</section>
