# Agente local para Windows

Este es el módulo que ejecuta la lectura de `Analisis.txt` en el equipo Windows.

También puede desplegarlo desde el botón **Desplegar agente** del encabezado del dashboard. Ese botón abre una página protegida desde la que se descargan el script y la configuración.

1. Copie `config.example.json` como `config.json` y configure la ruta, URL y `apiKey`.
2. Configure el mismo valor como `AGENT_API_KEY` en el servidor PHP.
3. Ejecute PowerShell como usuario con acceso al archivo:

```powershell
powershell.exe -ExecutionPolicy Bypass -File .\QuasarAgent.ps1
```

El agente recuerda la última línea enviada en `C:\ProgramData\QuasarAgent\state.json`, revisa el archivo cada cinco segundos y reintenta después de errores de red.
