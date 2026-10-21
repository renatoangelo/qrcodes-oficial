<?php
/** Lista dos QR codes, com busca e filtro por situação. */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir();

$destinos = Destinos::todos();
$totais = Estatisticas::totais($destinos);
$busca = trim((string) ($_GET['busca'] ?? ''));
$filtro = (string) ($_GET['situacao'] ?? '');

$lista = array_filter($destinos, function (array $qr, string $alias) use ($busca, $filtro): bool {
    if ($filtro !== '' && ($qr['status'] ?? 'ativo') !== $filtro) {
        return false;
    }
    if ($busca === '') {
        return true;
    }

    return mb_stripos($alias . ' ' . ($qr['url'] ?? '') . ' ' . ($qr['obs'] ?? ''), $busca) !== false;
}, ARRAY_FILTER_USE_BOTH);
uksort($lista, fn ($a, $b) => ($totais[$b] ?? 0) <=> ($totais[$a] ?? 0) ?: strcasecmp((string) $a, (string) $b));

topo('QR codes', $usuario, 'qrcodes.php');
?>
<div class="cabecalho">
  <h1>QR codes</h1>
  <a class="botao" href="<?= h(painel('novo.php')) ?>"><?= icone('mais') ?> Novo QR code</a>
</div>

<form class="filtros" method="get">
  <label class="filtros__busca"><?= icone('busca') ?><span class="so-leitor">Buscar</span>
    <input type="search" name="busca" value="<?= h($busca) ?>" placeholder="Buscar por alias, destino ou observação"></label>
  <select name="situacao" aria-label="Situação">
    <option value="">Todos</option>
    <option value="ativo"<?= $filtro === 'ativo' ? ' selected' : '' ?>>Ativos</option>
    <option value="pausado"<?= $filtro === 'pausado' ? ' selected' : '' ?>>Pausados</option>
  </select>
  <button class="botao botao--secundario" type="submit">Filtrar</button>
</form>

<?php if (!$lista): ?>
<p class="vazio">Nenhum QR code encontrado.</p>
<?php else: ?>
<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Alias</th><th class="col-destino">Destino</th><th class="num">Leituras</th><th>Situação</th><th class="col-acoes"><span class="so-leitor">Ações</span></th></tr></thead>
  <tbody>
<?php foreach ($lista as $alias => $qr): $alias = (string) $alias; $link = Destinos::endereco($alias, $qr); ?>
    <tr>
      <td><a class="forte" href="<?= h(painel('qrcode.php', ['alias' => $alias])) ?>"><?= h($alias) ?></a>
        <?php if (($qr['formato'] ?? '') === 'antigo'): ?><span class="selo" title="Importado do sistema antigo: o QR usa o endereço longo, igual ao impresso">antigo</span><?php endif; ?></td>
      <td class="destino col-destino"><a href="<?= h($qr['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($qr['url']) ?></a></td>
      <td class="num"><?= numero($totais[$alias] ?? 0) ?></td>
      <td><span class="situacao situacao--<?= h($qr['status'] ?? 'ativo') ?>"><?= ($qr['status'] ?? 'ativo') === 'pausado' ? 'Pausado' : 'Ativo' ?></span></td>
      <td class="acoes col-acoes">
        <a class="botao botao--icone" href="<?= h(painel('qrcode.php', ['alias' => $alias])) ?>" title="Editar"><?= icone('editar') ?><span class="so-leitor">Editar <?= h($alias) ?></span></a>
        <a class="botao botao--icone" href="<?= h(painel('imagem.php', ['alias' => $alias, 'formato' => 'png', 'tamanho' => 'tela'])) ?>" target="_blank" title="Ver QR code"><?= icone('qr') ?><span class="so-leitor">Ver QR code de <?= h($alias) ?></span></a>
        <button class="botao botao--icone" type="button" data-copiar="<?= h($link) ?>" title="Copiar link"><?= icone('copiar') ?><span class="so-leitor">Copiar link de <?= h($alias) ?></span></button>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>
<p class="legenda"><?= count($lista) ?> de <?= count($destinos) ?> QR codes. Leituras de robôs (pré-visualização de link, buscadores) não entram na contagem.</p>
<?php endif; ?>
<?php rodape(); ?>
