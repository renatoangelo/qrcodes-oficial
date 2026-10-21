<?php
/**
 * Entrada principal.
 *   /            → destino padrão
 *   /<alias>     → destino do QR code (o .htaccess envia para cá como index.php?a=<alias>)
 * O painel fica em /admin.
 */
require __DIR__ . '/app/bootstrap.php';

$alias = isset($_GET['a']) && is_string($_GET['a']) ? trim(rawurldecode($_GET['a']), '/') : '';

Redirecionador::atender($alias, 'curto');
