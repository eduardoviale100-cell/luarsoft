<?php
/**
 * modules/ordenes/_estados.php
 * ------------------------------------------------------------------
 * Catálogo único del flujo de estados de una Orden de Reparación,
 * usado por nuevo.php, editar.php, listado.php e imprimir.php para
 * mantener exactamente la misma lista e iconografía en todo el módulo.
 *
 * Incluye también los estados "legacy" (En espera / En reparación /
 * Reparado) del sistema anterior, para que órdenes ya guardadas con
 * esos valores se sigan mostrando correctamente y no queden con un
 * estado en blanco al editarlas.
 */

$ESTADOS_FLUJO = [
    'Ingresado' => ['badge' => 'neutral', 'icon' => 'bi-box-arrow-in-down'],
    'En Diagnóstico' => ['badge' => 'info', 'icon' => 'bi-search'],
    'Esperando Aprobación de Presupuesto' => ['badge' => 'warning', 'icon' => 'bi-hourglass-split'],
    'En Reparación / Cambio de Pieza' => ['badge' => 'info', 'icon' => 'bi-tools'],
    'Listo para Entrega' => ['badge' => 'success', 'icon' => 'bi-check-circle'],
    'Entregado' => ['badge' => 'neutral', 'icon' => 'bi-check2-all'],
];

$ESTADOS_LEGACY = [
    'En espera' => ['badge' => 'warning', 'icon' => 'bi-hourglass-split'],
    'En reparación' => ['badge' => 'info', 'icon' => 'bi-tools'],
    'Reparado' => ['badge' => 'success', 'icon' => 'bi-check-circle'],
];

$ESTADOS_TODOS = array_merge($ESTADOS_FLUJO, $ESTADOS_LEGACY);

$TIPOS_EQUIPO = ['Laptop', 'PC Escritorio', 'All in One', 'Impresora', 'Otro'];

function badgeEstadoOrden(string $estado, array $ESTADOS_TODOS): string
{
    $info = $ESTADOS_TODOS[$estado] ?? ['badge' => 'neutral', 'icon' => 'bi-question-circle'];
    return '<span class="badge-status ' . $info['badge'] . '"><i class="bi ' . $info['icon'] . '"></i> ' . htmlspecialchars($estado) . '</span>';
}
