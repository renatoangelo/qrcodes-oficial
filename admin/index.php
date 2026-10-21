<?php
/**
 * Dashboard: números do período, leituras por dia (30 dias), por mês (12 meses), por QR code,
 * por aparelho e por hora do dia. O filtro no topo mostra um QR code só.
 */
require __DIR__ . '/_inicio.php';
require QR_APP . '/visao/graficos.php';
$usuario = Sessao::exigir();

$destinos = Destinos::todos();
$filtro = (string) ($_GET['qr'] ?? '');
if (!isset($destinos[$filtro])) {
    $filtro = '';
}

$totais = Estatisticas::totais($destinos);
$d = Estatisticas::dashboard($filtro !== '' ? $filtro : null);
$totalGeral = $filtro !== '' ? $totais[$filtro] : array_sum($totais);
$anteriores = $filtro !== ''
    ? (int) ($destinos[$filtro]['anteriores'] ?? 0)
    : array_sum(array_map(fn (array $q): int => (int) ($q['anteriores'] ?? 0), $destinos));

$mesesCurtos = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
$nomeMes = fn (string $am): string => $mesesCurtos[(int) substr($am, 5, 2)];
$ultimoDia = array_key_last($d['dias']);

$aparelhos = [];
foreach (Estatisticas::APARELHOS as $chave => $nome) {
    $aparelhos[$nome] = $d['aparelhos'][$chave];
}
arsort($aparelhos);
$links = [];
foreach (array_keys($d['qrcodes']) as $alias) {
    $links[(string) $alias] = painel('qrcode.php', ['alias' => (string) $alias]);
}

topo('Dashboard', $usuario, 'index.php');
?>
<div class="cabecalho">
  <h1>Dashboard</h1>
  <form method="get" class="em-linha">
    <label class="so-leitor" for="filtro-qr">QR code</label>
    <select id="filtro-qr" name="qr" data-enviar-ao-mudar>
      <option value="">Todos os QR codes</option>
<?php foreach (array_keys($destinos) as $alias): ?>
      <option value="<?= h((string) $alias) ?>"<?= $filtro === (string) $alias ? ' selected' : '' ?>><?= h((string) $alias) ?></option>
<?php endforeach; ?>
    </select>
    <noscript><button class="botao botao--secundario" type="submit">Filtrar</button></noscript>
  </form>
</div>

<div class="numeros">
  <div class="cartao numero"><span class="numero__valor"><?= numero($d['hoje']) ?></span><span class="numero__rotulo">leituras hoje</span></div>
  <div class="cartao numero"><span class="numero__valor"><?= numero($d['trinta']) ?></span><span class="numero__rotulo">nos últimos 30 dias</span></div>
  <div class="cartao numero"><span class="numero__valor"><?= numero($d['mes']) ?></span><span class="numero__rotulo">neste mês</span></div>
  <div class="cartao numero"><span class="numero__valor"><?= numero($totalGeral) ?></span><span class="numero__rotulo">desde o início</span></div>
</div>

<section class="cartao">
  <?= grafico_colunas($d['dias'],
      fn (string $dia, int $i): string => ($i % 5 === 0 || $dia === $ultimoDia) ? date('d/m', strtotime($dia)) : '',
      fn (string $dia): string => date('d/m', strtotime($dia)),
      'Leituras por dia · últimos 30 dias') ?>
</section>

<div class="grade-2">
<?php if ($filtro === ''): ?>
  <section class="cartao"><?= grafico_barras(array_slice($d['qrcodes'], 0, 10, true), 'Por QR code · últimos 30 dias', $links) ?></section>
<?php endif; ?>
  <section class="cartao"><?= grafico_barras($aparelhos, 'Por aparelho · últimos 30 dias') ?></section>
</div>

<section class="cartao">
  <?= grafico_colunas($d['horas'],
      fn (int $h): string => $h % 3 === 0 ? $h . 'h' : '',
      fn (int $h): string => sprintf('%02dh às %02dh59', $h, $h),
      'Por hora do dia · últimos 30 dias') ?>
</section>

<section class="cartao">
  <?= grafico_colunas($d['meses'],
      fn (string $am): string => $nomeMes($am),
      fn (string $am): string => $nomeMes($am) . '/' . substr($am, 0, 4),
      'Leituras por mês · últimos 12 meses') ?>
</section>

<?php if ($anteriores > 0): ?>
<p class="legenda">“Desde o início” inclui <?= numero($anteriores) ?> leituras do sistema antigo. Elas não têm data, por isso não aparecem nos gráficos. Leituras de robôs (pré-visualização de links, buscadores) não são contadas.</p>
<?php else: ?>
<p class="legenda">Leituras de robôs (pré-visualização de links, buscadores) não são contadas.</p>
<?php endif; ?>
<?php rodape(); ?>
