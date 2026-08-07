<section class="hero-strip" id="resumen">
        <div class="hero-copy">
          <small>Sistema operativo</small>
          <h2>Lectura y análisis automático de variables</h2>
        </div>
        <div class="hero-file">
          <strong>Analisis.txt</strong>
          <span id="heroFileMeta">—</span>
        </div>
      </section>

      <section class="dashboard-equipment-filter" aria-label="Filtro global por equipo">
        <div>
          <small>Vista del dashboard</small>
          <strong>Filtrar todas las mediciones por equipo</strong>
        </div>
        <label class="field">
          <span>Equipo</span>
          <select id="equipmentFilter">
            <option value="">Todos los equipos</option>
            <?php foreach (($equipmentOptions ?? []) as $equipment): ?>
              <option value="<?= e($equipment['identificador']) ?>" <?= ($selectedEquipment ?? '') === $equipment['identificador'] ? 'selected' : '' ?>><?= e($equipment['nombre']) ?> · <?= e($equipment['identificador']) ?> (<?= (int) $equipment['total'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
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
            <div class="kpi-label">TSF promedio (Seg)</div>
            <div class="kpi-value" id="avgTiempo">0 Seg</div>
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
            <div class="kpi-label">Razón O/A promedio</div>
            <div class="kpi-value" id="avgRazon">0</div>
            <div class="kpi-foot">Incluye todos los valores</div>
          </div>
        </article>

        <article class="kpi">
          <div class="kpi-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 3v13"/><path d="M8 12a4 4 0 1 0 8 0V7a4 4 0 0 0-8 0v5Z"/><path d="M7 21h10"/>
            </svg>
          </div>
          <div>
            <div class="kpi-label">Conductividad promedio (mS/cm)</div>
            <div class="kpi-value" id="avgConductividadKpi">0 mS/cm</div>
            <div class="kpi-foot">Promedio del equipo seleccionado</div>
          </div>
        </article>

      </section>

      <section class="panel dashboard-recent" aria-label="Últimos registros del equipo seleccionado">
        <div class="panel-head">
          <div class="panel-title">
            <h3>Últimos registros recibidos</h3>
            <p id="recentRecordsContext">Lecturas más recientes según el equipo seleccionado</p>
          </div>
          <a class="text-link recent-all-link" href="<?= e(url('mediciones')) ?>">Ver todas las mediciones</a>
        </div>
        <div class="recent-measurements" id="recentMeasurements"></div>
        <div class="empty recent-empty" id="recentMeasurementsEmpty" hidden>No hay mediciones disponibles para este equipo.</div>
      </section>

      <section class="dashboard-grid" id="graficos">
        <article class="panel dashboard-chart-full">
          <div class="panel-head">
            <div class="panel-title">
              <h3>Evolución de TSF (Seg)</h3>
              <p>Serie cronológica de TSF expresada en segundos</p>
            </div>
            <div class="legend">
              <span class="legend-item"><i class="legend-dot" style="background:#2368e8"></i>TSF (Seg)</span>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="tsfChart"></canvas>
          </div>
        </article>

        <article class="panel dashboard-chart-full">
          <div class="panel-head">
            <div class="panel-title">
              <h3>Evolución de Razón O/A</h3>
              <p>Serie cronológica de la relación orgánico/acuoso</p>
            </div>
            <div class="legend">
              <span class="legend-item"><i class="legend-dot" style="background:#e99a18"></i>Razón O/A</span>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="razonChart"></canvas>
          </div>
        </article>

        <article class="panel dashboard-chart-full">
          <div class="panel-head">
            <div class="panel-title">
              <h3>Evolución de Conductividad (mS/cm)</h3>
              <p>Serie cronológica de conductividad en mS/cm</p>
            </div>
            <div class="legend">
              <span class="legend-item"><i class="legend-dot" style="background:#17a6b6"></i>Conductividad (mS/cm)</span>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="conductividadChart"></canvas>
          </div>
        </article>

      </section>

      <section class="range-grid">
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de TSF (Seg)</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minTiempo">0 Seg</b></div><div style="text-align:right"><small>Máximo</small><b id="maxTiempo">0 Seg</b></div></div>
          <div class="bar"><i style="width:84%"></i></div>
        </article>
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de Razón O/A</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minRazon">0</b></div><div style="text-align:right"><small>Máximo</small><b id="maxRazon">0</b></div></div>
          <div class="bar"><i style="width:72%; background:linear-gradient(90deg,#e99a18,#ffbd50)"></i></div>
        </article>
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de Conductividad (mS/cm)</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minConductividad">0 mS/cm</b></div><div style="text-align:right"><small>Máximo</small><b id="maxConductividad">0 mS/cm</b></div></div>
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
              <option value="valid">Conductividad ≥ 0</option>
              <option value="negative">Conductividad &lt; 0</option>
              <option value="zero">Conductividad = 0</option>
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
                <th class="sortable" data-sort="iso">Fecha ↕</th>
                <th>Hora</th>
                <th class="sortable" data-sort="tiempo">TSF (Seg) ↕</th>
                <th class="sortable" data-sort="razon">Razón O/A ↕</th>
                <th class="sortable" data-sort="conductividad">Conductividad (mS/cm) ↕</th>
                <th>Archivo</th>
                <th>Equipo</th>
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
