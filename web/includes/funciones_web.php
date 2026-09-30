<?php
/** Escapa una cadena para salida segura en HTML. */
function h($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Formatea un número como moneda en Soles (S/). */
function moneda($valor): string
{
    return 'S/ ' . number_format((float)$valor, 2);
}

function limpiar($valor): string
{
    return trim((string)($valor ?? ''));
}

/** ¿Hay una sesión de administrador/técnico (tabla usuarios) activa? */
function esAdmin(): bool
{
    return !empty($_SESSION['usuario']) && !empty($_SESSION['id_usuario']);
}

/** ¿Hay una sesión de cliente web (tabla clientes_web) activa y aprobada? */
function esClienteActivo(): bool
{
    return !empty($_SESSION['cliente_web_id']) && ($_SESSION['cliente_web_estado'] ?? '') === 'activo';
}

/** Verdadero si el visitante puede ver el catálogo completo (admin o cliente aprobado). */
function tieneAccesoCompleto(): bool
{
    return esAdmin() || esClienteActivo();
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash_web'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function obtenerFlash(): ?array
{
    if (!empty($_SESSION['flash_web'])) {
        $f = $_SESSION['flash_web'];
        unset($_SESSION['flash_web']);
        return $f;
    }
    return null;
}
