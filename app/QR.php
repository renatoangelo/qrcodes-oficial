<?php
/**
 * Geração das imagens de QR code, com a biblioteca phpqrcode (LGPL, em app/phpqrcode).
 *
 * - Correção de erro nível Q (até ~25% da imagem danificada ainda lê), melhor para impressão.
 * - PNG: tamanho em pixels pedido (pré-visualização ou alta resolução para impressão). Precisa do GD.
 * - SVG: vetor puro, sem GD, para gráfica e qualquer tamanho.
 * - Margem de 4 módulos (a "zona silenciosa" que os leitores exigem).
 */

final class QR
{
    private const MARGEM = 4;

    public static function carregar(): void
    {
        if (!class_exists('QRcode', false)) {
            if (!defined('QR_LOG_DIR')) {
                define('QR_LOG_DIR', false);
            }
            require_once QR_APP . '/phpqrcode/qrlib.php';
        }
    }

    public static function pngDisponivel(): bool
    {
        return function_exists('imagecreate') && function_exists('imagepng');
    }

    /** Matriz do QR: lista de linhas, cada uma com '1' (escuro) e '0' (claro), já com a margem. */
    public static function matriz(string $conteudo): array
    {
        self::carregar();
        $linhas = QRcode::text($conteudo, false, QR_ECLEVEL_Q, 1, 0);
        $largura = strlen($linhas[0] ?? '');
        $vazia = str_repeat('0', $largura + 2 * self::MARGEM);
        $saida = array_fill(0, self::MARGEM, $vazia);
        foreach ($linhas as $linha) {
            $saida[] = str_repeat('0', self::MARGEM) . $linha . str_repeat('0', self::MARGEM);
        }

        return array_merge($saida, array_fill(0, self::MARGEM, $vazia));
    }

    /** SVG vetorial: um único caminho, módulos escuros em preto sobre fundo branco. */
    public static function svg(string $conteudo): string
    {
        $m = self::matriz($conteudo);
        $n = count($m);
        $caminho = '';
        foreach ($m as $y => $linha) {
            // junta os módulos escuros seguidos de cada linha num retângulo só
            if (preg_match_all('/1+/', $linha, $blocos, PREG_OFFSET_CAPTURE)) {
                foreach ($blocos[0] as [$bloco, $x]) {
                    $caminho .= 'M' . $x . ' ' . $y . 'h' . strlen($bloco) . 'v1h-' . strlen($bloco) . 'z';
                }
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $n . ' ' . $n . '" width="' . ($n * 10) . '" height="' . ($n * 10) . '" shape-rendering="crispEdges">'
            . '<rect width="' . $n . '" height="' . $n . '" fill="#ffffff"/>'
            . '<path fill="#000000" d="' . $caminho . '"/></svg>' . "\n";
    }

    /** PNG com aproximadamente $pixels de lado (inteiro de pixels por módulo, para ficar nítido). */
    public static function png(string $conteudo, int $pixels): string
    {
        $m = self::matriz($conteudo);
        $n = count($m);
        $escala = max(1, (int) floor($pixels / $n));
        $lado = $n * $escala;

        $img = imagecreate($lado, $lado);
        $branco = imagecolorallocate($img, 255, 255, 255);
        $preto = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $branco);
        foreach ($m as $y => $linha) {
            for ($x = 0, $t = strlen($linha); $x < $t; $x++) {
                if ($linha[$x] === '1') {
                    imagefilledrectangle($img, $x * $escala, $y * $escala, ($x + 1) * $escala - 1, ($y + 1) * $escala - 1, $preto);
                }
            }
        }
        ob_start();
        imagepng($img, null, 9);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
