document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('menuButton');
  const overlay = document.getElementById('overlay');
  button?.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
  overlay?.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
  document.querySelectorAll('[data-module-table]').forEach(panel => {
    const search = panel.querySelector('[data-table-search]');
    const status = panel.querySelector('[data-table-status]');
    const rows = [...panel.querySelectorAll('tbody tr')];
    const count = panel.querySelector('[data-table-count]');
    const filter = () => {
      const query = search.value.trim().toLowerCase();
      const selected = status.value;
      let visible = 0;
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = (!query || text.includes(query)) && (!selected || text.includes(selected));
        row.hidden = !show;
        if (show) visible++;
      });
      count.textContent = `Mostrando ${visible} registros`;
    };
    search?.addEventListener('input', filter);
    status?.addEventListener('change', filter);
  });

  document.querySelectorAll('[data-measurements-table]').forEach(panel => {
    const search = panel.querySelector('[data-measurement-search]');
    const equipment = panel.querySelector('[data-measurement-equipment]');
    const from = panel.querySelector('[data-measurement-from]');
    const to = panel.querySelector('[data-measurement-to]');
    const clear = panel.querySelector('[data-measurement-clear]');
    const count = panel.querySelector('[data-measurement-count]');
    const visibleTotal = panel.querySelector('[data-measurement-visible]');
    const pagination = panel.querySelector('[data-measurement-pagination]');
    const empty = panel.querySelector('[data-measurement-empty]');
    const note = panel.querySelector('[data-measurement-note]');
    const rows = [...panel.querySelectorAll('tbody tr')];
    const pageSize = 25;
    let page = 1;

    const render = () => {
      const query = search.value.trim().toLowerCase();
      const selectedEquipment = equipment.value.toLowerCase();
      const firstDate = from.value;
      const lastDate = to.value;
      const filtered = rows.filter(row => {
        const date = row.dataset.date || '';
        return (!query || row.textContent.toLowerCase().includes(query)) &&
          (!selectedEquipment || row.dataset.equipment === selectedEquipment) &&
          (!firstDate || date >= firstDate) && (!lastDate || date <= lastDate);
      });
      const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
      page = Math.min(page, pages);
      rows.forEach(row => { row.hidden = true; });
      filtered.slice((page - 1) * pageSize, page * pageSize).forEach(row => { row.hidden = false; });
      visibleTotal.textContent = String(filtered.length);
      count.textContent = filtered.length ? `Mostrando ${(page-1)*pageSize+1}–${Math.min(page*pageSize,filtered.length)} de ${filtered.length} registros` : 'No hay resultados para estos filtros';
      empty.hidden = filtered.length > 0;
      note.hidden = !(query || selectedEquipment || firstDate || lastDate);
      note.textContent = `Filtros activos · ${filtered.length} coincidencia${filtered.length === 1 ? '' : 's'}`;
      pagination.innerHTML = '';
      if (pages > 1) {
        const addButton = (label, target, disabled = false, active = false) => {
          const button = document.createElement('button');
          button.type = 'button'; button.className = `page-btn${active ? ' active' : ''}`;
          button.textContent = label; button.disabled = disabled;
          button.addEventListener('click', () => { page = target; render(); panel.scrollIntoView({behavior:'smooth',block:'start'}); });
          pagination.appendChild(button);
        };
        addButton('‹', page - 1, page === 1);
        const start = Math.max(1, Math.min(page - 2, pages - 4));
        const end = Math.min(pages, start + 4);
        for (let number = start; number <= end; number++) addButton(String(number), number, false, number === page);
        addButton('›', page + 1, page === pages);
      }
    };
    [search, equipment, from, to].forEach(control => control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', () => { page = 1; render(); }));
    clear.addEventListener('click', () => { search.value=''; equipment.value=''; from.value=''; to.value=''; page=1; render(); });
    render();
  });
});
