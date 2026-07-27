// utils/pdfLoader.js
// Escribe una pantalla de carga en una ventana ya abierta (window.open('', '_blank'))
// mientras se hace el fetch + se genera el PDF. Cuando el PDF está listo,
// finalizarSalida() de cada composable redirige esa misma ventana al blob
// (ventanaPreabierta.location.href = url), reemplazando este loader.

export function mostrarLoaderPdf(ventana, mensaje = 'Generando reporte PDF...') {
    if (!ventana || ventana.closed) return

    ventana.document.open()
    ventana.document.write(`
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Generando PDF...</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: #0f172a;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .loader-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
    color: #e2e8f0;
    text-align: center;
    padding: 24px;
  }
  .spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(245, 158, 11, 0.25);
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .titulo { font-size: 1rem; font-weight: 600; letter-spacing: 0.02em; }
  .subtitulo { font-size: 0.8rem; color: #94a3b8; }
</style>
</head>
<body>
  <div class="loader-box">
    <div class="spinner"></div>
    <div class="titulo">${mensaje}</div>
    <div class="subtitulo">Esto puede tardar unos segundos...</div>
  </div>
</body>
</html>
    `)
    ventana.document.close()
}