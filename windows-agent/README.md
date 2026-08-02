# Agente local para Windows

Este es el módulo que ejecuta la lectura de `Analisis.txt` en el equipo Windows.

También puede desplegarlo desde el botón **Desplegar agente** del encabezado del dashboard. Ese botón abre una página protegida desde la que se descargan el script y la configuración.

## Lector web (recomendado)

No es necesario instalar un agente si puede mantener una ventana abierta en cada computador. Ingrese al panel desde Microsoft Edge o Google Chrome, abra **Agente Windows**, complete los datos, seleccione `Analisis.txt` y pulse **Iniciar lectura**. Por seguridad, el navegador solicita que el usuario seleccione el archivo; PHP no puede acceder directamente a archivos de otro computador. El servidor debe publicarse mediante HTTPS, ya que el selector continuo de archivos solo está disponible en contextos seguros (o en localhost).

Mantenga la pestaña abierta. El lector comprueba el archivo periódicamente, conserva en el navegador la última línea confirmada y reintenta los errores de red. Después de reiniciar el computador o cerrar el navegador se debe volver a seleccionar el archivo.

## Instalación como tarea de Windows (alternativa)

1. Configure `AGENT_API_KEY` en el servidor PHP.
2. Ingrese al panel, abra **Desplegar agente** y descargue `Instalar-Quasar.ps1` en cada computador.
3. Abra PowerShell como administrador y ejecute:

```powershell
powershell.exe -ExecutionPolicy Bypass -File .\Instalar-Quasar.ps1
```

El asistente solicita la ruta del TXT, el nombre visible, un identificador único y la clave. Comprueba todos los datos, instala los archivos en `C:\ProgramData\QuasarAgent`, registra la tarea programada **Quasar Agent** para el inicio de Windows y la inicia inmediatamente. Puede ejecutar nuevamente el instalador para corregir la configuración.

Para despliegues automatizados también admite parámetros:

```powershell
.\Instalar-Quasar.ps1 -Unattended -SourceFile 'C:\SistemaTXT\Entrada\Analisis.txt' -ApiKey 'clave' -EquipmentName 'Equipo 01' -EquipmentId 'planta-01'
```

## Instalación manual

Como alternativa, copie `config.example.json` como `config.json`, configure sus valores y ejecute directamente `QuasarAgent.ps1`.

El agente recuerda la última línea enviada en `C:\ProgramData\QuasarAgent\state.json`, revisa el archivo cada cinco segundos y reintenta después de errores de red. Cada solicitud incluye la línea, `equipmentId` y `equipmentName`. El servidor valida la API key y el formato; con MySQL guarda el equipo, archivo y medición en una transacción, y sin MySQL agrega la línea a `Analisis.txt`.
