/**
 * assets/js/escaner.js
 * ------------------------------------------------------------------
 * Escáner de Código de Barras por Cámara — componente compartido.
 * Se carga una sola vez y se puede invocar desde cualquier página
 * (Productos, POS) llamando a `ERPEscaner.abrir(function (codigo) {...})`.
 * Inyecta su propio modal en el DOM la primera vez que se usa, así que
 * no requiere agregar ningún HTML nuevo en los archivos PHP que lo usan
 * — solo el botón que lo llama.
 *
 * Usa la librería ZXing (auto-alojada en assets/vendor/zxing/), que
 * decodifica códigos de barras 1D (EAN-13, Code128, etc.) y QR desde el
 * video de la cámara del dispositivo en tiempo real.
 *
 * Limitación honesta: acceder a la cámara requiere un "contexto seguro"
 * del navegador — funciona en `http://localhost/...` sin problema, pero
 * si accedes al sistema desde otro equipo de tu red por IP (por ejemplo
 * `http://192.168.1.5/luarsoft/`), la mayoría de navegadores bloquean el
 * uso de la cámara salvo que el sitio use HTTPS. En ese caso, el botón
 * de lector físico USB (que no necesita cámara) sigue funcionando igual.
 */
(function () {
  "use strict";

  let lector = null;
  let modalEl = null;
  let callbackActual = null;
  let dispositivos = [];

  function crearModalSiNoExiste() {
    if (modalEl) return modalEl;

    modalEl = document.createElement("div");
    modalEl.className = "modal fade";
    modalEl.id = "modalEscanerCodigoBarras";
    modalEl.tabIndex = -1;
    modalEl.innerHTML =
      '<div class="modal-dialog modal-dialog-centered">' +
      '  <div class="modal-content">' +
      '    <div class="modal-header">' +
      '      <h5 class="modal-title"><i class="bi bi-upc-scan"></i> Escanear Código de Barras</h5>' +
      '      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>' +
      '    </div>' +
      '    <div class="modal-body text-center">' +
      '      <div id="escanerVideoWrap" style="position:relative; background:#000; border-radius:10px; overflow:hidden;">' +
      '        <video id="escanerVideo" style="width:100%; max-height:320px; display:block;"></video>' +
      '        <div class="escaner-linea"></div>' +
      '      </div>' +
      '      <p class="text-muted small mt-2 mb-1"><i class="bi bi-info-circle"></i> Apunta la cámara directamente al código de barras del producto.</p>' +
      '      <div id="escanerSelectCamara" class="mt-2" style="display:none;">' +
      '        <select id="escanerSelectCamaraInput" class="form-select form-select-sm"></select>' +
      '      </div>' +
      '      <div id="escanerError" class="alert alert-warning small mt-2" style="display:none;"></div>' +
      '    </div>' +
      '    <div class="modal-footer">' +
      '      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>' +
      '    </div>' +
      '  </div>' +
      '</div>';
    document.body.appendChild(modalEl);

    modalEl.addEventListener("hidden.bs.modal", detener);
    return modalEl;
  }

  function mostrarError(mensaje) {
    const el = document.getElementById("escanerError");
    if (el) { el.textContent = mensaje; el.style.display = "block"; }
  }

  async function iniciar(deviceId) {
    const videoEl = document.getElementById("escanerVideo");
    try {
      await lector.decodeFromVideoDevice(deviceId || null, videoEl, function (resultado, error) {
        if (resultado && callbackActual) {
          const codigo = resultado.getText();
          const cb = callbackActual;
          detener();
          const modal = window.bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();
          cb(codigo);
        }
        // Los errores de "no encontrado en este frame" son normales y
        // constantes mientras se apunta la cámara; se ignoran a propósito.
      });
    } catch (e) {
      mostrarError("No se pudo acceder a la cámara. Revisa los permisos del navegador o usa el lector físico USB en su lugar.");
    }
  }

  function detener() {
    if (lector) {
      try { lector.reset(); } catch (e) { /* ya estaba detenido */ }
    }
  }

  /**
   * Abre el modal de escaneo por cámara. `callback` recibe el código de
   * barras detectado (string) apenas se reconoce uno.
   */
  window.ERPEscaner = {
    abrir: async function (callback) {
      if (!window.ZXing) {
        alert("El módulo de escaneo no se pudo cargar. Verifica tu conexión o usa el lector físico USB.");
        return;
      }
      callbackActual = callback;
      crearModalSiNoExiste();
      document.getElementById("escanerError").style.display = "none";

      if (!lector) {
        lector = new window.ZXing.BrowserMultiFormatReader();
      }

      const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();

      try {
        dispositivos = await window.ZXing.BrowserMultiFormatReader.listVideoInputDevices();
      } catch (e) {
        dispositivos = [];
      }

      if (dispositivos.length > 1) {
        const wrap = document.getElementById("escanerSelectCamara");
        const select = document.getElementById("escanerSelectCamaraInput");
        select.innerHTML = dispositivos.map(function (d, i) {
          return '<option value="' + d.deviceId + '">' + (d.label || ("Cámara " + (i + 1))) + '</option>';
        }).join("");
        wrap.style.display = "block";
        select.onchange = function () { detener(); iniciar(select.value); };
        iniciar(select.value);
      } else {
        document.getElementById("escanerSelectCamara").style.display = "none";
        iniciar(dispositivos[0] ? dispositivos[0].deviceId : null);
      }
    },
  };
})();
