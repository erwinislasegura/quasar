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
});
