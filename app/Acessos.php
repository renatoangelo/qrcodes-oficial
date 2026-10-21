<?php
/**
 * Registro das leituras de QR code: uma linha por leitura em dados/acessos/AAAA-MM.php.
 *
 * Formato da linha (separada por tabulação): data e hora · alias · aparelho · entrada
 *   aparelho: celular, tablet, computador ou robo (pré-visualizações de link, buscadores)
 *   entrada:  curto (/alias) ou antigo (/qrcode/index.php?target=alias)
 * Não guarda IP, navegador completo nem nada que identifique quem leu.
 */

final class Acessos
{
    public static function registrar(string $alias, string $entrada): void
    {
        $linha = implode("\t", [date('Y-m-d H:i:s'), self::limpar($alias), self::aparelho(), $entrada]);
        Armazem::acrescentar('acessos/' . date('Y-m'), $linha);
    }

    /** Classifica o aparelho pelo User-Agent, sem guardá-lo. */
    public static function aparelho(?string $agente = null): string
    {
        $agente ??= (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($agente === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|curl|wget|python|headless/i', $agente)) {
            return 'robo';
        }
        if (preg_match('/ipad|tablet|kindle|silk|(android(?!.*mobile))/i', $agente)) {
            return 'tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android|windows phone/i', $agente)) {
            return 'celular';
        }

        return 'computador';
    }

    /**
     * Lê as leituras de um período. Devolve uma lista de [quando, alias, aparelho, entrada].
     * $de e $ate no formato AAAA-MM-DD.
     */
    public static function periodo(string $de, string $ate): array
    {
        $saida = [];
        $mes = new DateTimeImmutable(substr($de, 0, 7) . '-01');
        $fim = new DateTimeImmutable(substr($ate, 0, 7) . '-01');
        while ($mes <= $fim) {
            foreach (Armazem::linhas('acessos/' . $mes->format('Y-m')) as $linha) {
                $campos = explode("\t", $linha);
                if (count($campos) >= 4) {
                    $dia = substr($campos[0], 0, 10);
                    if ($dia >= $de && $dia <= $ate) {
                        $saida[] = $campos;
                    }
                }
            }
            $mes = $mes->modify('+1 month');
        }

        return $saida;
    }

    private static function limpar(string $texto): string
    {
        return mb_substr(str_replace(["\t", "\r", "\n"], ' ', $texto), 0, 120);
    }
}
