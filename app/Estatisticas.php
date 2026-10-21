<?php
/**
 * Contagens a partir dos registros de leitura (dados/acessos/). Leituras de robôs (pré-visualização
 * de link, buscadores) ficam de fora dos totais.
 */

final class Estatisticas
{
    public const APARELHOS = ['celular' => 'Celular', 'computador' => 'Computador', 'tablet' => 'Tablet'];

    /** Total de leituras de cada alias, desde sempre: registradas + as do sistema antigo. */
    public static function totais(array $destinos): array
    {
        $totais = [];
        foreach ($destinos as $alias => $qr) {
            $totais[$alias] = (int) ($qr['anteriores'] ?? 0);
        }
        foreach (self::meses() as $mes) {
            foreach (Armazem::linhas('acessos/' . $mes) as $linha) {
                $c = explode("\t", $linha);
                if (count($c) >= 4 && $c[2] !== 'robo' && isset($totais[$c[1]])) {
                    $totais[$c[1]]++;
                }
            }
        }

        return $totais;
    }

    /**
     * Números do dashboard: leituras por dia (30 dias), por mês (12 meses), por hora e por aparelho
     * (30 dias) e por QR code (30 dias). Com $alias, conta só aquele QR code.
     */
    public static function dashboard(?string $alias = null): array
    {
        $hoje = new DateTimeImmutable('today');
        $inicioDias = $hoje->modify('-29 days');
        $inicioMeses = $hoje->modify('first day of this month')->modify('-11 months');

        $dados = ['dias' => [], 'meses' => [], 'horas' => array_fill(0, 24, 0),
            'aparelhos' => array_fill_keys(array_keys(self::APARELHOS), 0), 'qrcodes' => [],
            'hoje' => 0, 'mes' => 0, 'trinta' => 0];
        for ($d = $inicioDias; $d <= $hoje; $d = $d->modify('+1 day')) {
            $dados['dias'][$d->format('Y-m-d')] = 0;
        }
        for ($m = $inicioMeses; $m <= $hoje; $m = $m->modify('+1 month')) {
            $dados['meses'][$m->format('Y-m')] = 0;
        }

        $diaHoje = $hoje->format('Y-m-d');
        $mesAtual = $hoje->format('Y-m');
        $desde = $inicioDias->format('Y-m-d');
        foreach (Acessos::periodo($inicioMeses->format('Y-m-d'), $diaHoje) as [$quando, $qr, $aparelho]) {
            if ($aparelho === 'robo' || ($alias !== null && $qr !== $alias)) {
                continue;
            }
            $dia = substr($quando, 0, 10);
            $mes = substr($quando, 0, 7);
            if (isset($dados['meses'][$mes])) {
                $dados['meses'][$mes]++;
            }
            if ($mes === $mesAtual) {
                $dados['mes']++;
            }
            if ($dia < $desde) {
                continue;
            }
            $dados['trinta']++;
            $dados['dias'][$dia] = ($dados['dias'][$dia] ?? 0) + 1;
            $dados['horas'][(int) substr($quando, 11, 2)]++;
            if (isset($dados['aparelhos'][$aparelho])) {
                $dados['aparelhos'][$aparelho]++;
            }
            $dados['qrcodes'][$qr] = ($dados['qrcodes'][$qr] ?? 0) + 1;
            if ($dia === $diaHoje) {
                $dados['hoje']++;
            }
        }
        arsort($dados['qrcodes']);

        return $dados;
    }

    /** Meses que têm registro, do mais antigo ao mais recente (AAAA-MM). */
    public static function meses(): array
    {
        $meses = [];
        foreach (glob(QR_DADOS . '/acessos/*.php') ?: [] as $arquivo) {
            $nome = basename($arquivo, '.php');
            if (preg_match('/^\d{4}-\d{2}$/', $nome)) {
                $meses[] = $nome;
            }
        }
        sort($meses);

        return $meses;
    }
}
