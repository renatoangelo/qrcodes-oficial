<?php
/**
 * Imagem do QR code de um alias, só para quem está logado.
 *   formato=svg                    vetor (pré-visualização e download)
 *   formato=png&tamanho=tela       ~600 px, para ver
 *   formato=png&tamanho=impressao  ~3000 px, para gráfica
 *   baixar=1                       força o download com o nome do alias
 */
require __DIR__ . '/_inicio.php';
Sessao::exigir();

$alias = (string) ($_GET['alias'] ?? '');
$qr = Destinos::obter($alias);
if (!$qr) {
    http_response_code(404);
    exit('QR code não encontrado.');
}

$conteudo = Destinos::endereco($alias, $qr);
$formato = ($_GET['formato'] ?? 'svg') === 'png' ? 'png' : 'svg';
$nome = preg_replace('/[^A-Za-z0-9_-]+/', '-', $alias) ?: 'qrcode';

if ($formato === 'png' && !QR::pngDisponivel()) {
    http_response_code(501);
    exit('Este servidor não tem a extensão GD do PHP: use o SVG.');
}

if ($formato === 'svg') {
    header('Content-Type: image/svg+xml; charset=utf-8');
    $saida = QR::svg($conteudo);
    $arquivo = "qrcode-{$nome}.svg";
} else {
    $pixels = ($_GET['tamanho'] ?? 'tela') === 'impressao' ? 3000 : 600;
    header('Content-Type: image/png');
    $saida = QR::png($conteudo, $pixels);
    $arquivo = "qrcode-{$nome}-{$pixels}px.png";
}

header('Cache-Control: private, max-age=300');
if (!empty($_GET['baixar'])) {
    header('Content-Disposition: attachment; filename="' . $arquivo . '"');
}
echo $saida;
