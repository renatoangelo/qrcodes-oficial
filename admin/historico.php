<?php
/** Histórico geral: quem mudou o quê e quando (QR codes, usuários, ajustes, logins). */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir();

$eventos = Historico::recentes(null, 300);
$rotulos = [
    'criar' => 'Criou o QR code', 'destino' => 'Trocou o destino de', 'observacao' => 'Editou as observações de',
    'pausar' => 'Pausou', 'reativar' => 'Reativou', 'apagar' => 'Apagou o QR code', 'importar' => 'Importou',
    'ajuste' => 'Mudou o ajuste', 'login' => 'Entrou no painel', 'conta' => 'Atualizou a própria conta',
    'usuario-criado' => 'Criou o usuário', 'usuario-removido' => 'Removeu o usuário', 'usuario-perfil' => 'Mudou o perfil de',
    'usuario-senha' => 'Definiu nova senha para', 'usuario-ativar' => 'Reativou o usuário', 'usuario-desativar' => 'Desativou o usuário',
];
$mostrarLogins = !empty($_GET['logins']);
if (!$mostrarLogins) {
    $eventos = array_values(array_filter($eventos, fn (array $e): bool => ($e['acao'] ?? '') !== 'login'));
}

topo('Histórico', $usuario, 'historico.php');
?>
<div class="cabecalho">
  <h1>Histórico</h1>
  <a class="botao botao--secundario" href="<?= h(painel('historico.php', $mostrarLogins ? [] : ['logins' => 1])) ?>"><?= $mostrarLogins ? 'Esconder logins' : 'Mostrar logins' ?></a>
</div>
<?php if (!$eventos): ?>
<p class="vazio">Nada registrado ainda.</p>
<?php else: ?>
<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Quando</th><th>Quem</th><th>O quê</th><th>Detalhes</th></tr></thead>
  <tbody>
<?php foreach ($eventos as $ev): $d = $ev['detalhes'] ?? []; $acao = (string) ($ev['acao'] ?? ''); $alvo = (string) ($ev['alvo'] ?? ''); ?>
    <tr>
      <td class="sem-quebra"><?= quando($ev['quando'] ?? null) ?></td>
      <td><?= h($ev['usuario'] ?? '') ?></td>
      <td><?= h($rotulos[$acao] ?? $acao) ?>
        <?php if (in_array($acao, ['criar', 'destino', 'observacao', 'pausar', 'reativar'], true) && Destinos::obter($alvo)): ?>
          <a href="<?= h(painel('qrcode.php', ['alias' => $alvo])) ?>"><?= h($alvo) ?></a>
        <?php elseif ($acao !== 'login' && $acao !== 'conta'): ?>
          <strong><?= h($alvo) ?></strong>
        <?php endif; ?></td>
      <td class="destino">
<?php if (array_key_exists('antes', $d) && $alvo !== 'segredo'): ?>
        <s><?= h(is_scalar($d['antes']) ? (string) $d['antes'] : '') ?></s> → <?= h(is_scalar($d['depois'] ?? null) ? (string) $d['depois'] : '') ?>
<?php elseif (isset($d['url'])): ?>
        <?= h((string) $d['url']) ?>
<?php elseif (isset($d['qrcodes'])): ?>
        <?= h(implode(', ', (array) $d['qrcodes'])) ?>
<?php endif; ?>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php rodape(); ?>
