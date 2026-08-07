const RAW_DATA = window.QUASAR_DATA || [];

    const fmt = (value, decimals = 2) =>
      new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      }).format(value);
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));

    const state = {
      filtered: [...RAW_DATA],
      dashboard: [...RAW_DATA],
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
      avgConductividadKpi: document.getElementById('avgConductividadKpi'),
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
      equipmentFilter: document.getElementById('equipmentFilter'),
      dateFilter: document.getElementById('dateFilter'),
      statusFilter: document.getElementById('statusFilter'),
      pageSize: document.getElementById('pageSize'),
      lastReadText: document.getElementById('lastReadText'),
      heroFileMeta: document.getElementById('heroFileMeta'),
      recentMeasurements: document.getElementById('recentMeasurements'),
      recentMeasurementsEmpty: document.getElementById('recentMeasurementsEmpty'),
      recentRecordsContext: document.getElementById('recentRecordsContext'),
      tooltip: document.getElementById('tooltip')
    };

    function average(items, key) {
      return items.length ? items.reduce((sum, item) => sum + item[key], 0) / items.length : 0;
    }

    function renderStats(data) {
      const uniqueDates = [...new Set(data.map(item => item.fecha))];

      els.totalRecords.textContent = data.length;
      els.dateCount.textContent = `${uniqueDates.length} fechas diferentes`;
      els.avgTiempo.textContent = fmt(average(data, 'tiempo'), 2);
      els.avgRazon.textContent = fmt(average(data, 'razon'), 3);
      els.avgConductividadKpi.textContent = fmt(average(data, 'conductividad'), 2);

      const tiempoValues = data.map(x => x.tiempo);
      const razonValues = data.map(x => x.razon);
      const conductivityValues = data.map(x => x.conductividad);

      els.minTiempo.textContent = tiempoValues.length ? fmt(Math.min(...tiempoValues), 2) : '0';
      els.maxTiempo.textContent = tiempoValues.length ? fmt(Math.max(...tiempoValues), 2) : '0';
      els.minRazon.textContent = razonValues.length ? fmt(Math.min(...razonValues), 3) : '0';
      els.maxRazon.textContent = razonValues.length ? fmt(Math.max(...razonValues), 3) : '0';
      els.minConductividad.textContent = conductivityValues.length ? fmt(Math.min(...conductivityValues), 0) : '0';
      els.maxConductividad.textContent = conductivityValues.length ? fmt(Math.max(...conductivityValues), 0) : '0';

      const last = [...data].sort((a,b) => a.iso.localeCompare(b.iso)).at(-1);
      els.lastReadText.textContent = last ? `${last.fecha} · ${last.hora}` : 'Sin mediciones';
      els.heroFileMeta.textContent = `${data.length} líneas procesadas`;
      renderRecentMeasurements(data);
    }

    function renderRecentMeasurements(data) {
      const latest = [...data].sort((a,b) => b.iso.localeCompare(a.iso)).slice(0, 4);
      const selectedLabel = els.equipmentFilter?.selectedOptions?.[0]?.textContent?.trim() || 'Todos los equipos';
      els.recentRecordsContext.textContent = selectedLabel === 'Todos los equipos'
        ? 'Las 4 lecturas más recientes de todos los equipos'
        : `Las 4 lecturas más recientes de ${selectedLabel.replace(/\s*\(\d+\)$/, '')}`;
      els.recentMeasurementsEmpty.hidden = latest.length > 0;
      els.recentMeasurements.innerHTML = latest.map(item => `
        <article class="recent-record">
          <div class="recent-record-time"><strong>${escapeHtml(item.fecha)}</strong><span>${escapeHtml(item.hora)}</span></div>
          <div class="recent-record-equipment"><strong>${escapeHtml(item.equipo || 'Equipo sin nombre')}</strong><span>${escapeHtml(item.equipoIdentificador || 'Sin identificador')}</span></div>
          <div class="recent-record-value"><small>TSF</small><strong>${fmt(item.tiempo, 3)}</strong></div>
          <div class="recent-record-value"><small>Razón O/A</small><strong>${fmt(item.razon, 6)}</strong></div>
          <div class="recent-record-value"><small>Conductividad</small><strong>${fmt(item.conductividad, 2)}</strong></div>
        </article>
      `).join('');
    }

    function initFilters() {
      const uniqueDates = [...new Set(RAW_DATA.map(item => item.fecha))];

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
      if (item.conductividad < 0) return {label: 'Conductividad < 0', className: 'error'};
      if (item.conductividad === 0) return {label: 'Conductividad = 0', className: 'warn'};
      return {label: 'Conductividad > 0', className: 'ok'};
    }

    function applyFilters() {
      const query = els.searchInput.value.trim().toLowerCase();
      const date = els.dateFilter.value;
      const status = els.statusFilter.value;

      state.dashboard = [...RAW_DATA];
      state.filtered = state.dashboard.filter(item => {
        const haystack = [
          item.fecha,
          item.hora,
          item.tiempo,
          item.razon,
          item.conductividad,
          item.archivo,
          item.equipo,
          item.equipoIdentificador
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
      renderStats(state.dashboard);
      renderTable();
      drawCharts();
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

      els.tableBody.innerHTML = rows.map(item => `
          <tr>
            <td class="date-cell"><strong>${item.fecha}</strong></td>
            <td>${item.hora}</td>
            <td class="metric">${fmt(item.tiempo, 3)}</td>
            <td class="metric">${fmt(item.razon, 6)}</td>
            <td class="metric">${fmt(item.conductividad, 2)}</td>
            <td>${item.archivo}</td>
            <td>${item.equipo || '—'}</td>
          </tr>
        `).join('');

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

      els.equipmentFilter.addEventListener('change', () => {
        const target = new URL(window.location.href);
        if (els.equipmentFilter.value) target.searchParams.set('equipo', els.equipmentFilter.value);
        else target.searchParams.delete('equipo');
        window.location.assign(target.toString());
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

    const chartDefinitions = [
      {id: 'tsfChart', key: 'tiempo', label: 'TSF', color: '#2368e8', decimals: 3},
      {id: 'razonChart', key: 'razon', label: 'Razón O/A', color: '#e99a18', decimals: 6},
      {id: 'conductividadChart', key: 'conductividad', label: 'Conductividad', color: '#17a6b6', decimals: 2}
    ];

    function drawChart(definition) {
      const canvas = document.getElementById(definition.id);
      if (!canvas) return;
      const rect = canvas.getBoundingClientRect();
      const dpr = window.devicePixelRatio || 1;
      canvas.width = Math.max(1, Math.floor(rect.width * dpr));
      canvas.height = Math.max(1, Math.floor(rect.height * dpr));

      const ctx = canvas.getContext('2d');
      ctx.scale(dpr, dpr);

      const width = rect.width;
      const height = rect.height;
      const pad = {top: 15, right: 24, bottom: 34, left: 45};
      const plotW = width - pad.left - pad.right;
      const plotH = height - pad.top - pad.bottom;

      ctx.clearRect(0,0,width,height);
      ctx.font = '10px system-ui';
      ctx.textBaseline = 'middle';

      const data = state.dashboard.filter(item => Number.isFinite(Number(item[definition.key]))).map(item => ({
        ...item,
        [definition.key]: Number(item[definition.key])
      }));
      if (!data.length) {
        ctx.fillStyle = '#8a96a8';
        ctx.textAlign = 'center';
        ctx.fillText('No hay mediciones para este equipo', width / 2, height / 2);
        canvas._chartMeta = null;
        return;
      }
      const values = data.map(item => item[definition.key]);
      const rawMin = Math.min(...values);
      const rawMax = Math.max(...values);
      const span = rawMax - rawMin;
      const rangePad = span > 0 ? span * 0.1 : Math.max(definition.key === 'razon' ? 0.1 : 1, Math.abs(rawMax) * 0.1);
      const minY = rawMin - rangePad;
      const maxY = Math.max(rawMax + rangePad, minY + (definition.key === 'razon' ? 0.2 : 1));

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
        const axisDecimals = definition.key === 'razon' ? 2 : (maxY - minY < 10 ? 1 : 0);
        ctx.fillText(fmt(value, axisDecimals), pad.left - 8, py);
      }

      ctx.textAlign = 'center';
      const labelCount = width < 520 ? 4 : 6;
      for (let i=0; i<labelCount; i++) {
        const index = Math.round((i/(labelCount-1)) * (data.length-1));
        const px = x(index);
        const label = data[index].fecha.slice(0,5);
        ctx.fillText(label, px, height - 14);
      }

      if (data.length > 1) {
        ctx.beginPath();
        data.forEach((item,index) => {
          const px = x(index);
          const py = y(item[definition.key]);
          if (index === 0) ctx.moveTo(px,py);
          else ctx.lineTo(px,py);
        });
        ctx.strokeStyle = definition.color;
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.stroke();
      }

      data.forEach((item, index) => {
        ctx.beginPath();
        ctx.arc(x(index), y(item[definition.key]), data.length === 1 ? 5 : 2.5, 0, Math.PI * 2);
        ctx.fillStyle = definition.color;
        ctx.fill();
      });

      canvas._chartMeta = {x,y,pad,plotW,plotH,data,definition};
    }

    function drawCharts() {
      chartDefinitions.forEach(drawChart);
    }

    function setupChartTooltips() {
      chartDefinitions.forEach(definition => {
        const canvas = document.getElementById(definition.id);
        if (!canvas) return;
        canvas.addEventListener('mousemove', event => {
        const meta = canvas._chartMeta;
        if (!meta) return;

        const rect = canvas.getBoundingClientRect();
        const mx = event.clientX - rect.left;
        const relative = Math.min(1, Math.max(0, (mx - meta.pad.left) / meta.plotW));
        const index = Math.round(relative * (meta.data.length - 1));
        const item = meta.data[index];
        if (!item) return;

        els.tooltip.innerHTML = `
          <strong>${item.fecha} ${item.hora}</strong><br>
          ${meta.definition.label}: ${fmt(item[meta.definition.key], meta.definition.decimals)}
        `;
        els.tooltip.style.left = `${event.clientX}px`;
        els.tooltip.style.top = `${event.clientY}px`;
        els.tooltip.style.opacity = '1';
        });

        canvas.addEventListener('mouseleave', () => {
          els.tooltip.style.opacity = '0';
        });
      });
    }

    function exportCSV() {
      const data = sortData(state.filtered);
      const header = ['Fecha','Hora','TSF','Razon O/A','Conductividad','Estado','Archivo','Equipo'];
      const rows = data.map(item => [
        item.fecha,
        item.hora,
        String(item.tiempo).replace('.',','),
        String(item.razon).replace('.',','),
        String(item.conductividad).replace('.',','),
        getStatus(item).label,
        item.archivo,
        item.equipo || ''
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

    initFilters();
    setupTableEvents();
    setupChartTooltips();
    setupActions();
    applyFilters();

    let resizeTimer;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(drawCharts, 120);
    });
