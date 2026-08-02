const RAW_DATA = window.QUASAR_DATA || [];

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
      avgTSF: document.getElementById('avgTiempo'),
      avgRazon: document.getElementById('avgRazon'),
      negativeCount: document.getElementById('negativeCount'),
      validPercent: document.getElementById('validPercent'),
      validCount: document.getElementById('validCount'),
      invalidCount: document.getElementById('invalidCount'),
      avgConductividad: document.getElementById('avgConductividad'),
      qualityChart: document.getElementById('qualityChart'),
      minTSF: document.getElementById('minTiempo'),
      maxTSF: document.getElementById('maxTiempo'),
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
            <td class="date-cell"><strong>${item.fecha}</strong></td>
            <td>${item.hora}</td>
            <td class="metric">${fmt(item.tiempo, 3)}</td>
            <td class="metric">${fmt(item.razon, 6)}</td>
            <td class="metric">${fmt(item.conductividad, 2)}</td>
            <td><span class="badge ${status.className}">${status.label}</span></td>
            <td>${item.archivo}</td>
            <td>${item.equipo || '—'}</td>
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
          TSF: ${fmt(item.tiempo,3)} · Conductividad: ${fmt(item.conductividad,2)}
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
