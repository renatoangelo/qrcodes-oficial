<?php
/**
 * Gráficos do dashboard, desenhados em SVG no servidor (sem biblioteca e sem JavaScript).
 * A política de segurança do painel bloqueia estilo embutido, então as medidas vão em atributos do SVG.
 * O detalhe de cada barra aparece ao passar o mouse (title) e todo gráfico tem a versão em tabela.
 */

/**
 * Colunas verticais, uma série só.
 * $valores: [chave => número]; $rotulo(chave, índice): texto do eixo ('' para pular);
 * $dica(chave): texto da dica, sem o número.
 */
function grafico_colunas(array $valores, callable $rotulo, callable $dica, string $titulo): string
{
    $n = count($valores);
    $maximo = max(1, ...array_values($valores));
    $total = array_sum($valores);

    $barras = '';
    $eixo = '';
    $linhas = '';
    $i = 0;
    foreach ($valores as $chave => $valor) {
        $altura = $valor > 0 ? max(1.5, $valor / $maximo * 94) : 0;
        $texto = $dica($chave) . ': ' . numero($valor) . ($valor === 1 ? ' leitura' : ' leituras');
        $barras .= '<g><title>' . h($texto) . '</title>'
            . '<rect class="grafico__alvo" x="' . $i . '" y="0" width="1" height="100"/>'
            . '<rect class="grafico__barra" x="' . ($i + 0.15) . '" y="' . round(100 - $altura, 2) . '" width="0.7" height="' . round($altura, 2) . '"/></g>';
        $eixo .= '<li>' . h($rotulo($chave, $i)) . '</li>';
        $linhas .= '<tr><td>' . h($dica($chave)) . '</td><td class="num">' . numero($valor) . '</td></tr>';
        $i++;
    }

    $pico = array_search(max($valores), $valores, true);
    $resumo = $total > 0
        ? 'Pico: ' . numero(max($valores)) . ' (' . h($dica($pico)) . ')'
        : 'Nenhuma leitura no período.';

    return '<figure class="grafico">'
        . '<figcaption class="grafico__topo"><span class="forte">' . h($titulo) . '</span><span class="legenda">' . $resumo . '</span></figcaption>'
        . '<svg class="grafico__area" viewBox="0 0 ' . $n . ' 100" preserveAspectRatio="none" role="img" aria-label="' . h($titulo) . '">'
        . '<line class="grafico__grade" x1="0" x2="' . $n . '" y1="6" y2="6"/>'
        . $barras
        . '<line class="grafico__base" x1="0" x2="' . $n . '" y1="100" y2="100"/></svg>'
        . '<ol class="grafico__eixo grafico__eixo--' . $n . '" aria-hidden="true">' . $eixo . '</ol>'
        . '<details class="grafico__tabela"><summary>Ver em tabela</summary><table class="tabela"><thead><tr><th>Período</th><th class="num">Leituras</th></tr></thead><tbody>' . $linhas . '</tbody></table></details>'
        . '</figure>';
}

/**
 * Barras horizontais com rótulo, número e porcentagem.
 * $itens: [rótulo => número]; $links: [rótulo => endereço] opcional.
 */
function grafico_barras(array $itens, string $titulo, array $links = []): string
{
    $total = array_sum($itens);
    $maximo = max(1, ...array_values($itens ?: [0]));
    $lista = '';
    foreach ($itens as $nome => $valor) {
        $nome = (string) $nome;
        $parte = $total > 0 ? round($valor / $total * 100) : 0;
        $largura = $valor > 0 ? max(1, $valor / $maximo * 100) : 0;
        $rotulo = isset($links[$nome]) ? '<a href="' . h($links[$nome]) . '">' . h($nome) . '</a>' : h($nome);
        $lista .= '<li><span class="barras__nome">' . $rotulo . '</span>'
            . '<span class="barras__valor">' . numero($valor) . ' <span class="legenda">' . $parte . '%</span></span>'
            . '<svg class="barras__trilho" viewBox="0 0 100 1" preserveAspectRatio="none" aria-hidden="true">'
            . '<rect class="barras__fundo" width="100" height="1"/><rect class="grafico__barra" width="' . round($largura, 2) . '" height="1"/></svg></li>';
    }

    return '<figure class="grafico"><figcaption class="grafico__topo"><span class="forte">' . h($titulo) . '</span></figcaption>'
        . ($total > 0 ? '<ul class="barras">' . $lista . '</ul>' : '<p class="vazio">Nenhuma leitura no período.</p>')
        . '</figure>';
}
