<?php
/**
 * Leva quem leu o QR code ao destino cadastrado. Usado pelas duas entradas:
 *   /alias                               (index.php, endereço curto)
 *   /qrcode/index.php?target=alias       (qrcode/index.php, endereço antigo dos QR codes impressos)
 *
 * Regras:
 * - alias ativo → destino dele;
 * - alias pausado, inexistente ou sem destino válido → destino padrão dos ajustes;
 * - sem destino padrão válido → mensagem simples, NUNCA um redirecionamento para o próprio
 *   sistema (era o que causava o laço infinito no sistema antigo).
 * A leitura é registrada em Acessos sem tocar nos destinos.
 */

final class Redirecionador
{
    public static function atender(string $alias, string $entrada): never
    {
        try {
            $qr = Destinos::obter($alias);
        } catch (RuntimeException $erro) {
            error_log('qrcodes: ' . $erro->getMessage());
            $qr = null;
        }

        if ($qr !== null) {
            Acessos::registrar($alias, $entrada);
        }

        $destino = ($qr !== null && ($qr['status'] ?? 'ativo') === 'ativo') ? (string) ($qr['url'] ?? '') : '';
        if (!Destinos::urlValida($destino)) {
            $destino = (string) Ajustes::obter('padrao', '');
        }

        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');

        if (!Destinos::urlValida($destino) || self::apontaParaSi($destino)) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
               . '<title>Link indisponível</title><p style="font:16px system-ui;padding:2rem">Este link não está disponível no momento.</p>';
            exit;
        }

        header('Location: ' . $destino, true, 302);
        exit;
    }

    /** O destino é o próprio sistema de QR codes? (evita laço) */
    private static function apontaParaSi(string $destino): bool
    {
        $base = parse_url(Ajustes::base(), PHP_URL_HOST);
        $alvo = parse_url($destino, PHP_URL_HOST);

        return $base !== null && $alvo !== null && strcasecmp($base, $alvo) === 0;
    }
}
