# Lector web para Windows

El lector funciona desde el menú **Lector Windows** y no instala programas ni tareas en el computador.

1. Configure `AGENT_API_KEY` en el servidor PHP.
2. Abra Quasar mediante HTTPS desde Microsoft Edge o Google Chrome.
3. Ingrese a **Lector Windows**, escriba el nombre e identificador del equipo y la clave de conexión.
4. Seleccione `Analisis.txt`, pulse **Iniciar lectura** y mantenga la pestaña abierta.

El navegador debe solicitar permiso para acceder al archivo porque PHP, ejecutado en el servidor, no puede abrir directamente un archivo de otro computador. El lector conserva localmente la última línea confirmada, envía solamente líneas nuevas y reintenta automáticamente si la red falla.

Después de cerrar el navegador o reiniciar Windows se debe abrir nuevamente la página, seleccionar el archivo y pulsar **Iniciar lectura**. El nombre, identificador, intervalo y posición de lectura permanecen guardados en ese navegador; la clave no se almacena.
