<?php
/**
 * includes/document_modal.php
 * ------------------------------------------------------------------
 * Modal único y reutilizable para previsualizar cualquier documento
 * imprimible del sistema (boleta, factura, ticket POS, recibo de
 * orden de reparación) sin abrir pestañas nuevas ni recargar la
 * página. Se incluye una sola vez en el layout general; cada botón
 * de "Ver/Imprimir" en el sistema llama a ERP.verDocumento(url, titulo)
 * en JS (ver assets/js/app.js) para poblarlo y mostrarlo.
 */
?>
<div class="modal fade modal-documento" id="modalDocumento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-file-earmark-text"></i>
          <span id="modalDocumentoTitulo">Documento</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="doc-frame-wrap">
          <iframe id="modalDocumentoFrame" src="about:blank" title="Vista previa del documento"></iframe>
        </div>
      </div>
      <div class="modal-footer">
        <a id="modalDocumentoPantallaCompleta" href="#" class="btn btn-secondary btn-sm">
          <i class="bi bi-arrows-fullscreen"></i> Pantalla completa
        </a>
        <button type="button" class="btn btn-info btn-sm" onclick="ERP.imprimirDocumentoModal()">
          <i class="bi bi-printer"></i> Imprimir
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="ERP.imprimirDocumentoModal()">
          <i class="bi bi-download"></i> Descargar PDF
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="bi bi-x-lg"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>
