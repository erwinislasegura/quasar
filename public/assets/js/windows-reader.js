(() => {
  const root = document.querySelector('.web-reader');
  if (!root) return;

  const get = id => document.getElementById(id);
  const fields = {
    name: get('readerEquipmentName'), id: get('readerEquipmentId'), key: get('readerApiKey'),
    interval: get('readerInterval'), customInterval: get('readerCustomInterval'),
    customIntervalField: get('readerCustomIntervalField'), file: get('readerFileName'), meta: get('readerFileMeta'),
    generateId: get('readerGenerateId'),
    start: get('readerStart'), stop: get('readerStop'), reset: get('readerReset'), select: get('readerSelectFile'),
    message: get('readerMessage'), badge: get('readerBadge'), state: get('readerState'),
    sent: get('readerSent'), confirmed: get('readerConfirmed'), lastCheck: get('readerLastCheck')
  };
  let fileHandle = null;
  let timer = null;
  let running = false;
  let busy = false;
  let sent = 0;

  const storageKey = () => `quasar-reader:${fields.id.value.trim()}:line`;
  const position = () => Math.max(0, Number.parseInt(localStorage.getItem(storageKey()) || '0', 10) || 0);
  const intervalSeconds = () => Number(fields.interval.value === 'custom' ? fields.customInterval.value : fields.interval.value);
  const validInterval = () => Number.isFinite(intervalSeconds()) && intervalSeconds() >= 1 && intervalSeconds() <= 3600;
  const saveSettings = () => localStorage.setItem('quasar-reader:settings', JSON.stringify({
    name: fields.name.value, id: fields.id.value, interval: fields.interval.value,
    customInterval: fields.customInterval.value
  }));
  const setStatus = (state, message, type = 'warn') => {
    fields.state.textContent = state;
    fields.message.textContent = message;
    fields.badge.textContent = state;
    fields.badge.className = `badge ${type === 'ok' ? 'ok' : 'warn'}`;
  };
  const ready = () => fileHandle && fields.id.value.trim() && fields.key.value.trim() && fields.name.value.trim() && validInterval();
  const updateReady = () => {
    fields.customIntervalField.hidden = fields.interval.value !== 'custom';
    fields.start.disabled = !ready() || running;
    fields.confirmed.textContent = String(position());
    saveSettings();
  };
  const updateFileMeta = file => {
    fields.file.textContent = file.name;
    fields.meta.textContent = `${file.size.toLocaleString()} bytes · última modificación ${new Date(file.lastModified).toLocaleString()}`;
  };

  try {
    const saved = JSON.parse(localStorage.getItem('quasar-reader:settings') || '{}');
    fields.name.value = saved.name || fields.name.value;
    fields.id.value = saved.id || '';
    fields.interval.value = saved.interval || '5';
    fields.customInterval.value = saved.customInterval || '15';
  } catch (_) {
    localStorage.removeItem('quasar-reader:settings');
  }

  fields.select.addEventListener('click', async () => {
    if (!window.showOpenFilePicker) {
      setStatus('Error', 'Este navegador no permite lectura continua. Use Microsoft Edge o Google Chrome actualizado.', 'error');
      return;
    }
    try {
      const handles = await window.showOpenFilePicker({
        types: [{ description: 'Archivo TXT', accept: { 'text/plain': ['.txt'] } }],
        excludeAcceptAllOption: true,
        multiple: false
      });
      const candidate = handles[0];
      const file = await candidate.getFile();
      if (file.name.toLowerCase() !== 'analisis.txt') {
        setStatus('Error', 'El archivo debe llamarse Analisis.txt. Seleccione el archivo correcto.', 'error');
        return;
      }
      fileHandle = candidate;
      updateFileMeta(file);
      setStatus('Detenido', 'Archivo listo. Pulse “Iniciar lectura”.');
      updateReady();
    } catch (error) {
      if (error.name !== 'AbortError') setStatus('Error', `No se pudo abrir el archivo: ${error.message}`, 'error');
    }
  });

  fields.generateId.addEventListener('click', () => {
    const randomPart = window.crypto?.randomUUID
      ? window.crypto.randomUUID().slice(0, 8)
      : Math.random().toString(36).slice(2, 10);
    fields.id.value = `windows-${randomPart}`;
    updateReady();
    fields.id.focus();
    setStatus('Detenido', 'Identificador creado. Puede conservarlo o escribir otro que sea único.');
  });

  async function check() {
    if (busy || !fileHandle) return;
    busy = true;
    setStatus('Leyendo', 'Revisando Analisis.txt…', 'ok');
    try {
      const file = await fileHandle.getFile();
      updateFileMeta(file);
      const lines = (await file.text()).split(/\r?\n/).filter(line => line.trim() !== '');
      let current = position();
      if (current > lines.length) {
        current = 0;
        localStorage.setItem(storageKey(), '0');
      }
      for (let index = current; index < lines.length; index += 1) {
        const response = await fetch(root.dataset.measurementsUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-API-Key': fields.key.value },
          body: JSON.stringify({ line: lines[index], equipmentId: fields.id.value.trim(), equipmentName: fields.name.value.trim() })
        });
        if (!response.ok) {
          const result = await response.json().catch(() => ({}));
          throw new Error(result.error || `el servidor respondió ${response.status}`);
        }
        localStorage.setItem(storageKey(), String(index + 1));
        fields.confirmed.textContent = String(index + 1);
        sent += 1;
        fields.sent.textContent = String(sent);
      }
      fields.lastCheck.textContent = new Date().toLocaleTimeString();
      setStatus('Conectado', lines.length === current ? 'Conectado. Esperando líneas nuevas…' : 'Las líneas nuevas fueron confirmadas por el servidor.', 'ok');
    } catch (error) {
      fields.lastCheck.textContent = new Date().toLocaleTimeString();
      setStatus('Error', `No fue posible leer o enviar: ${error.message}. Se reintentará automáticamente.`, 'error');
    } finally {
      busy = false;
    }
  }

  fields.start.addEventListener('click', async () => {
    if (!ready()) {
      setStatus('Error', 'Complete todos los datos, elija un intervalo válido y seleccione Analisis.txt.', 'error');
      return;
    }
    try {
      setStatus('Conectando', 'Validando la clave de conexión…');
      const response = await fetch(root.dataset.statusUrl, { headers: { 'X-API-Key': fields.key.value } });
      if (!response.ok) throw new Error(response.status === 401 ? 'la clave de conexión no es correcta' : `el servidor respondió ${response.status}`);
      fields.start.disabled = true;
      fields.stop.disabled = false;
      running = true;
      await check();
      if (running) timer = window.setInterval(check, intervalSeconds() * 1000);
    } catch (error) {
      setStatus('Error', `No se pudo iniciar: ${error.message}.`, 'error');
      updateReady();
    }
  });
  fields.stop.addEventListener('click', () => {
    window.clearInterval(timer);
    timer = null;
    running = false;
    fields.stop.disabled = true;
    setStatus('Detenido', 'Lectura detenida.');
    updateReady();
  });
  fields.reset.addEventListener('click', () => {
    if (window.confirm('¿Desea volver a enviar Analisis.txt desde la primera línea?')) {
      localStorage.removeItem(storageKey());
      sent = 0;
      fields.sent.textContent = '0';
      fields.confirmed.textContent = '0';
      setStatus(running ? 'Conectado' : 'Detenido', 'Posición reiniciada. Las líneas se reenviarán en la próxima revisión.', running ? 'ok' : 'warn');
    }
  });
  [fields.name, fields.id, fields.key, fields.interval, fields.customInterval].forEach(field => field.addEventListener('input', updateReady));
  updateReady();
})();
