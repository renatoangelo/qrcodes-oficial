<?php
/**
 * Endereço ANTIGO dos QR codes: /qrcode/index.php?target=<alias>.
 * Os QR codes impressos pelo sistema anterior apontam para cá. Este arquivo não pode sair daqui.
 * Sem "target", vale o alias "default", como no sistema antigo.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

$alias = isset($_GET['target']) && is_string($_GET['target']) && $_GET['target'] !== '' ? $_GET['target'] : 'default';

Redirecionador::atender($alias, 'antigo');
