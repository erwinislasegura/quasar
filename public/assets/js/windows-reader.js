(() => {
  const root = document.querySelector('.web-reader');
  if (!root) return;

  const get = id => document.getElementById(id);
  const fields = {
    name: get('readerEquipmentName'), id: get('readerEquipmentId'), key: get('readerApiKey'),
    interval: get('readerInterval'), file: get('readerFileName'), meta: get('readerFileMeta'),
    start: get('readerStart'), stop: get('readerStop'), reset: get('readerReset'), select: get('readerSelectFile'),
    message: get('readerMessage'), badge: get('readerBadge'), state: get('readerState'),
    sent: get('readerSent'), lastCheck: get('readerLastCheck')
  };
  let fileHandle = null;
  let timer = null;
  let busy = false;
  let sent = 0;

  const storageKey = () => `quasar-reader:${fields.id.value.trim()}:line`;
  const position = () => Number(localStorage.getItem(storageKey()) || 0);
  const saveSettings = () => localStorage.setItem('quasar-reader:settings', JSON.stringify({
    name: fields.name.value, id: fields.id.value, interval: fields.interval.value
  }));
  const show = (message, type = 'warn') => {
    fields.message.textContent = message;
    fields.badge.textContent = type === 'ok' ? 'Conectado' : type === 'error' ? 'Error' : 'En espera';
    fields.badge.className = `badge ${type === 'ok' ? 'ok' : 'warn'}`;
  };
  const ready = () => fileHandle && fields.id.value.trim() && fields.key.value.trim() && fields.name.value.trim();
  const updateReady = () => { fields.start.disabled = !ready() || Boolean(timer); saveSettings(); };

  try {
    const saved = JSON.parse(localStorage.getItem('quasar-reader:settings') || '{}');
    fields.name.value = saved.name || fields.name.value;
    fields.id.value = saved.id || '';
    fields.interval.value = saved.interval || '5';
  } catch (_) {}

  fields.select.addEventListener('click', async () => {
    if (!window.showOpenFilePicker) {
      show('Este navegador no permite lectura continua. Use Microsoft Edge o Google Chrome actualizado.', 'error');
      return;
    }
    try {
      [fileHandle] = await window.showOpenFilePicker({ types: [{ description: 'Archivo TXT', accept: { 'text/plain': ['.txt'] } }], multiple: false });
      const file = await fileHandle.getFile();
      fields.file.textContent = file.name;
      fields.meta.textContent = `${Math.round(file.size / 1024)} KB · última modificación ${new Date(file.lastModified).toLocaleString()}`;
      show('Archivo listo. Pulse Iniciar lectura.');
      updateReady();
    } catch (error) {
      if (error.name !== 'AbortError') show(`No se pudo abrir el archivo: ${error.message}`, 'error');
    }
  });

  async function check() {
    if (busy || !fileHandle) return;
    busy = true;
    try {
      const file = await fileHandle.getFile();
      const lines = (await file.text()).split(/\r?\n/).filter(line => line.trim() !== '');
      let current = position();
      if (current > lines.length) current = 0;
      for (let index = current; index < lines.length; index += 1) {
        const response = await fetch(root.dataset.measurementsUrl, {
          method: 'POST', headers: { 'Content-Type': 'application/json', 'X-API-Key': fields.key.value },
          body: JSON.stringify({ line: lines[index], equipmentId: fields.id.value.trim(), equipmentName: fields.name.value.trim() })
        });
        if (!response.ok) {
          const result = await response.json().catch(() => ({}));
          throw new Error(result.error || `El servidor respondió ${response.status}`);
        }
        localStorage.setItem(storageKey(), String(index + 1));
        sent += 1;
        fields.sent.textContent = String(sent);
      }
      fields.lastCheck.textContent = new Date().toLocaleTimeString();
      show(lines.length === current ? 'Conectado. Esperando nuevas líneas…' : 'Mediciones enviadas correctamente.', 'ok');
    } catch (error) {
      show(`No fue posible leer o enviar: ${error.message}. Se reintentará automáticamente.`, 'error');
    } finally { busy = false; }
  }

  fields.start.addEventListener('click', async () => {
    try {
      const response = await fetch(root.dataset.statusUrl, { headers: { 'X-API-Key': fields.key.value } });
      if (!response.ok) throw new Error(response.status === 401 ? 'La clave no es correcta' : `El servidor respondió ${response.status}`);
      fields.start.disabled = true; fields.stop.disabled = false; fields.state.textContent = 'Leyendo';
      await check();
      timer = window.setInterval(check, Number(fields.interval.value) * 1000);
    } catch (error) { show(`No se pudo iniciar: ${error.message}.`, 'error'); }
  });
  fields.stop.addEventListener('click', () => {
    window.clearInterval(timer); timer = null; fields.stop.disabled = true; fields.state.textContent = 'Detenido';
    show('Lectura detenida.'); updateReady();
  });
  fields.reset.addEventListener('click', () => {
    if (window.confirm('¿Desea volver a enviar el archivo desde la primera línea?')) {
      localStorage.removeItem(storageKey()); sent = 0; fields.sent.textContent = '0'; show('Posición reiniciada.');
    }
  });
  [fields.name, fields.id, fields.key, fields.interval].forEach(field => field.addEventListener('input', updateReady));
  updateReady();
})();
