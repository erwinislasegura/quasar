<section class="hero-strip" id="resumen">
        <div class="hero-copy">
          <small>Sistema operativo</small>
          <h2>Lectura y análisis automático de variables</h2>
          <p>Visualización de Fecha, Hora, TSF, Razón O/A y Conductividad, con detección de valores negativos y filtros para revisar cada medición.</p>
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
            <div class="kpi-label">TSF promedio</div>
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
            <div class="kpi-label">Razón O/A promedio</div>
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
              <p>Serie cronológica de TSF y Conductividad</p>
            </div>
            <div class="legend">
              <span class="legend-item"><i class="legend-dot" style="background:#2368e8"></i>TSF</span>
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
          <div class="range-head"><strong>Rango de TSF</strong><span>mínimo / máximo</span></div>
          <div class="range-values"><div><small>Mínimo</small><b id="minTiempo">0</b></div><div style="text-align:right"><small>Máximo</small><b id="maxTiempo">0</b></div></div>
          <div class="bar"><i style="width:84%"></i></div>
        </article>
        <article class="panel range-card">
          <div class="range-head"><strong>Rango de Razón O/A</strong><span>mínimo / máximo</span></div>
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
                <th class="sortable" data-sort="iso">Fecha ↕</th>
                <th>Hora</th>
                <th class="sortable" data-sort="tiempo">TSF ↕</th>
                <th class="sortable" data-sort="razon">Razón O/A ↕</th>
                <th class="sortable" data-sort="conductividad">Conductividad ↕</th>
                <th>Estado</th>
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
