<?php
/**
 * Início de toda página do painel: carrega o sistema, inicia a sessão e envia os cabeçalhos de
 * segurança. As páginas chamam Sessao::exigir() logo depois.
 */
require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

Sessao::iniciar();

/** Abre a página com o layout do painel. */
function topo(string $titulo, ?array $usuario = null, string $atual = ''): void
{
    require QR_APP . '/visao/topo.php';
}

function rodape(): void
{
    require QR_APP . '/visao/rodape.php';
}
