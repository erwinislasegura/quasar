(() => {
  const root = document.querySelector('.web-reader');
  if (!root) return;

  const get = id => document.getElementById(id);
  const fields = {
    name: get('readerEquipmentName'), id: get('readerEquipmentId'),
    interval: get('readerInterval'), customInterval: get('readerCustomInterval'),
    customIntervalField: get('readerCustomIntervalField'), file: get('readerFileName'), meta: get('readerFileMeta'),
    generateId: get('readerGenerateId'), install: get('readerInstall'),
    start: get('readerStart'), stop: get('readerStop'), reset: get('readerReset'), select: get('readerSelectFile'),
    message: get('readerMessage'), badge: get('readerBadge'), state: get('readerState'),
    sent: get('readerSent'), confirmed: get('readerConfirmed'), lastCheck: get('readerLastCheck')
  };
  let fileHandle = null;
  let timer = null;
  let commandTimer = null;
  let reloadTimer = null;
  let running = false;
  let busy = false;
  let sent = 0;
  let wakeLock = null;
  let releaseTabLock = null;
  let installPrompt = null;
  const AUTO_RELOAD_MS = 3 * 60 * 60 * 1000;

  const scheduleReload = () => {
    window.clearTimeout(reloadTimer);
    reloadTimer = window.setTimeout(() => {
      if (running) window.location.reload();
    }, AUTO_RELOAD_MS);
  };

  const checkRemoteCommand = async () => {
    if (!running || !fields.id.value.trim()) return;
    try {
      const commandUrl = new URL(root.dataset.commandUrl, window.location.origin);
      commandUrl.searchParams.set('equipmentId', fields.id.value.trim());
      const response = await fetch(commandUrl, {credentials:'same-origin', cache:'no-store'});
      if (!response.ok) return;
      const command = await response.json();
      if (command.refresh === true) window.location.reload();
    } catch (_) {
      // La lectura continúa aunque temporalmente no se pueda consultar una orden remota.
    }
  };

  const handleDatabase = () => new Promise((resolve, reject) => {
    const request = indexedDB.open('quasar-reader', 2);
    request.onupgradeneeded = () => {
      if (!request.result.objectStoreNames.contains('settings')) request.result.createObjectStore('settings');
      if (!request.result.objectStoreNames.contains('queue')) request.result.createObjectStore('queue', {keyPath:'key'});
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
  const saveFileHandle = async handle => {
    const db = await handleDatabase();
    await new Promise((resolve, reject) => {
      const request = db.transaction('settings', 'readwrite').objectStore('settings').put(handle, 'analysis-file');
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
    db.close();
  };
  const loadFileHandle = async () => {
    const db = await handleDatabase();
    const handle = await new Promise((resolve, reject) => {
      const request = db.transaction('settings').objectStore('settings').get('analysis-file');
      request.onsuccess = () => resolve(request.result || null);
      request.onerror = () => reject(request.error);
    });
    db.close();
    return handle;
  };
  const queuePut = async item => {
    const db = await handleDatabase();
    await new Promise((resolve,reject) => { const request=db.transaction('queue','readwrite').objectStore('queue').put(item); request.onsuccess=()=>resolve(); request.onerror=()=>reject(request.error); });
    db.close();
  };
  const queueItems = async equipmentId => {
    const db = await handleDatabase();
    const items = await new Promise((resolve,reject) => { const request=db.transaction('queue').objectStore('queue').getAll(); request.onsuccess=()=>resolve(request.result||[]); request.onerror=()=>reject(request.error); });
    db.close();
    return items.filter(item => item.equipmentId === equipmentId).sort((a,b)=>a.index-b.index);
  };
  const queueDelete = async key => {
    const db = await handleDatabase();
    await new Promise((resolve,reject) => { const request=db.transaction('queue','readwrite').objectStore('queue').delete(key); request.onsuccess=()=>resolve(); request.onerror=()=>reject(request.error); });
    db.close();
  };

  const acquireReaderLock = () => {
    if (!navigator.locks) return Promise.resolve(true);
    return new Promise(resolve => {
      navigator.locks.request('quasar-reader-active', {ifAvailable:true}, lock => {
        if (!lock) { resolve(false); return; }
        resolve(true);
        return new Promise(done => { releaseTabLock = done; });
      });
    });
  };
  const acquireWakeLock = async () => {
    if (!('wakeLock' in navigator) || document.visibilityState !== 'visible') return;
    try { wakeLock = await navigator.wakeLock.request('screen'); } catch (_) {}
  };

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
  const ready = () => fileHandle && fields.id.value.trim() && fields.name.value.trim() && validInterval();
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
    if (Object.prototype.hasOwnProperty.call(saved, 'key')) {
      delete saved.key;
      localStorage.setItem('quasar-reader:settings', JSON.stringify(saved));
    }
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
      await saveFileHandle(candidate);
      updateFileMeta(file);
      setStatus('Detenido', 'Archivo y ruta guardados en este equipo. Pulse “Iniciar lectura”.');
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
      const equipmentId = fields.id.value.trim();
      for (let index = current; index < lines.length; index += 1) {
        await queuePut({key:`${equipmentId}:${index}`,equipmentId,index,line:lines[index],equipmentName:fields.name.value.trim(),createdAt:Date.now()});
      }
      const pending = await queueItems(equipmentId);
      for (const item of pending) {
        const response = await fetch(root.dataset.measurementsUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ line: item.line, equipmentId: item.equipmentId, equipmentName: item.equipmentName })
        });
        if (!response.ok) {
          const result = await response.json().catch(() => ({}));
          throw new Error(result.error || `el servidor respondió ${response.status}`);
        }
        await queueDelete(item.key);
        localStorage.setItem(storageKey(), String(item.index + 1));
        fields.confirmed.textContent = String(item.index + 1);
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
      if (!await acquireReaderLock()) throw new Error('ya existe otra pestaña de Quasar realizando la lectura');
      if (fileHandle?.queryPermission && await fileHandle.queryPermission({mode: 'read'}) !== 'granted') {
        const permission = await fileHandle.requestPermission({mode: 'read'});
        if (permission !== 'granted') throw new Error('debe autorizar nuevamente la lectura del archivo guardado');
      }
      setStatus('Conectando', 'Validando la sesión del lector…');
      const statusUrl = new URL(root.dataset.statusUrl, window.location.origin);
      statusUrl.searchParams.set('equipmentId', fields.id.value.trim());
      statusUrl.searchParams.set('equipmentName', fields.name.value.trim());
      const response = await fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok) throw new Error(response.status === 401 ? 'la sesión expiró; vuelva a iniciar sesión' : `el servidor respondió ${response.status}`);
      const serverStatus = await response.json();
      if (serverStatus.newEquipment === true) {
        localStorage.setItem(storageKey(), '0');
        fields.confirmed.textContent = '0';
        setStatus('Sincronizando', 'Equipo registrado nuevamente. Reenviando las mediciones del archivo…', 'ok');
      }
      fields.start.disabled = true;
      fields.stop.disabled = false;
      running = true;
      localStorage.setItem('quasar-reader:running', '1');
      await acquireWakeLock();
      await check();
      if (running) {
        timer = window.setInterval(check, intervalSeconds() * 1000);
        commandTimer = window.setInterval(checkRemoteCommand, 30000);
        scheduleReload();
      }
    } catch (error) {
      if (!running && releaseTabLock) { releaseTabLock(); releaseTabLock = null; }
      setStatus('Error', `No se pudo iniciar: ${error.message}.`, 'error');
      updateReady();
    }
  });
  fields.stop.addEventListener('click', () => {
    window.clearInterval(timer);
    window.clearInterval(commandTimer);
    window.clearTimeout(reloadTimer);
    timer = null;
    commandTimer = null;
    reloadTimer = null;
    running = false;
    localStorage.removeItem('quasar-reader:running');
    if (releaseTabLock) { releaseTabLock(); releaseTabLock = null; }
    if (wakeLock) { wakeLock.release().catch(()=>{}); wakeLock = null; }
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
  [fields.name, fields.id, fields.interval, fields.customInterval].forEach(field => field.addEventListener('input', updateReady));

  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    installPrompt = event;
    fields.install.hidden = false;
  });
  fields.install.addEventListener('click', async () => {
    if (!installPrompt) return;
    installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    fields.install.hidden = true;
  });
  window.addEventListener('appinstalled', () => { fields.install.hidden = true; });
  document.addEventListener('visibilitychange', () => { if (running && document.visibilityState === 'visible') acquireWakeLock(); });
  if ('serviceWorker' in navigator) navigator.serviceWorker.register(root.dataset.swUrl).catch(()=>{});

  if (!fields.id.value.trim()) {
    const randomPart = window.crypto?.randomUUID
      ? window.crypto.randomUUID().slice(0, 8)
      : Math.random().toString(36).slice(2, 10);
    fields.id.value = `windows-${randomPart}`;
  }
  saveSettings();
  updateReady();

  loadFileHandle().then(async savedHandle => {
    if (!savedHandle) return;
    fileHandle = savedHandle;
    try {
      const file = await savedHandle.getFile();
      updateFileMeta(file);
      setStatus('Detenido', 'Configuración y ruta recuperadas. Pulse “Iniciar lectura”.');
    } catch (_) {
      fields.file.textContent = 'Analisis.txt guardado';
      fields.meta.textContent = 'La ruta está registrada; el navegador solicitará autorización al iniciar.';
    }
    updateReady();
    if (localStorage.getItem('quasar-reader:running') === '1' && (!savedHandle.queryPermission || await savedHandle.queryPermission({mode:'read'}) === 'granted')) {
      fields.start.click();
    }
  }).catch(() => updateReady());
})();
