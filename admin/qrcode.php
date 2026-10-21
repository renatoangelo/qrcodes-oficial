<?php
/**
 * Um QR code: imagem, links, downloads, editar destino e observações, pausar/reativar,
 * histórico e apagar (com confirmação dupla: confirmar e digitar o alias).
 */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir();

$alias = (string) ($_GET['alias'] ?? '');
$qr = Destinos::obter($alias);
if (!$qr) {
    http_response_code(404);
    avisar('QR code não encontrado.', 'erro');
    ir(painel('qrcodes.php'));
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $acao = (string) ($_POST['acao'] ?? '');
    try {
        if ($acao === 'salvar') {
            Destinos::alterar($alias, [
                'url' => trim((string) ($_POST['url'] ?? '')),
                'obs' => mb_substr(trim((string) ($_POST['obs'] ?? '')), 0, 500),
            ], $usuario['login']);
            avisar('Alterações salvas.');
        } elseif ($acao === 'pausar' || $acao === 'reativar') {
            Destinos::alterar($alias, ['status' => $acao === 'pausar' ? 'pausado' : 'ativo'], $usuario['login']);
            avisar($acao === 'pausar' ? 'QR code pausado: quem ler vai para o destino padrão.' : 'QR code reativado.');
        } elseif ($acao === 'apagar') {
            if ((string) ($_POST['confirmar_alias'] ?? '') !== $alias) {
                throw new RuntimeException('Para apagar, digite o alias exatamente como aparece.');
            }
            Destinos::apagar($alias, $usuario['login']);
            avisar('QR code "' . $alias . '" apagado.');
            ir(painel('qrcodes.php'));
        }
        ir(painel('qrcode.php', ['alias' => $alias]));
    } catch (RuntimeException $e) {
        $erro = $e->getMessage();
        $qr = Destinos::obter($alias) ?? $qr;
    }
}

$link = Destinos::endereco($alias, $qr);
$linkCurto = Ajustes::base() . '/' . rawurlencode($alias);
$total = Estatisticas::totais([$alias => $qr])[$alias] ?? 0;
$pausado = ($qr['status'] ?? 'ativo') === 'pausado';
$eventos = Historico::recentes($alias, 30);
$rotulos = ['criar' => 'Criado', 'destino' => 'Destino alterado', 'observacao' => 'Observações alteradas',
    'pausar' => 'Pausado', 'reativar' => 'Reativado', 'importar' => 'Importado'];

topo($alias, $usuario, 'qrcodes.php');
?>
<p class="migalha"><a href="<?= h(painel('qrcodes.php')) ?>">QR codes</a> › <?= h($alias) ?></p>

<div class="cabecalho">
  <h1><?= h($alias) ?> <span class="situacao situacao--<?= $pausado ? 'pausado' : 'ativo' ?>"><?= $pausado ? 'Pausado' : 'Ativo' ?></span></h1>
  <form method="post">
    <?= campo_csrf() ?>
    <input type="hidden" name="acao" value="<?= $pausado ? 'reativar' : 'pausar' ?>">
    <button class="botao botao--secundario" type="submit"><?= icone($pausado ? 'ativar' : 'pausar') ?> <?= $pausado ? 'Reativar' : 'Pausar' ?></button>
  </form>
</div>

<?php if ($erro): ?>
<p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>
<?php if ($pausado): ?>
<p class="aviso aviso--info">Pausado: quem ler este QR code vai para o destino padrão (<?= h(Ajustes::obter('padrao', '—')) ?>), não para o destino abaixo.</p>
<?php endif; ?>

<div class="grade-qr">
  <section class="cartao qr-imagem">
    <img src="<?= h(painel('imagem.php', ['alias' => $alias, 'formato' => 'svg', 'v' => substr(md5($link), 0, 8)])) ?>" alt="QR code de <?= h($alias) ?>" width="240" height="240">
    <p class="forte"><?= numero($total) ?> leituras</p>
<?php if ((int) ($qr['anteriores'] ?? 0) > 0): ?>
    <p class="legenda">Inclui <?= numero((int) $qr['anteriores']) ?> do sistema antigo.</p>
<?php endif; ?>
    <div class="botoes botoes--coluna">
      <a class="botao" href="<?= h(painel('imagem.php', ['alias' => $alias, 'formato' => 'png', 'tamanho' => 'impressao', 'baixar' => 1])) ?>"><?= icone('baixar') ?> PNG alta resolução</a>
      <a class="botao botao--secundario" href="<?= h(painel('imagem.php', ['alias' => $alias, 'formato' => 'svg', 'baixar' => 1])) ?>"><?= icone('baixar') ?> SVG vetorial</a>
    </div>
  </section>

  <div class="pilha">
    <section class="cartao">
      <h2>Links</h2>
      <dl class="links">
        <dt>Link do QR code</dt>
        <dd><code><?= h($link) ?></code>
          <button class="botao botao--icone" type="button" data-copiar="<?= h($link) ?>" title="Copiar"><?= icone('copiar') ?><span class="so-leitor">Copiar</span></button>
          <a class="botao botao--icone" href="<?= h($link) ?>" target="_blank" rel="noopener" title="Testar"><?= icone('abrir') ?><span class="so-leitor">Testar</span></a></dd>
<?php if (($qr['formato'] ?? '') === 'antigo'): ?>
        <dt>Link curto (para reimpressões)</dt>
        <dd><code><?= h($linkCurto) ?></code>
          <button class="botao botao--icone" type="button" data-copiar="<?= h($linkCurto) ?>" title="Copiar"><?= icone('copiar') ?><span class="so-leitor">Copiar</span></button></dd>
<?php endif; ?>
      </dl>
<?php if (($qr['formato'] ?? '') === 'antigo'): ?>
      <p class="legenda">Importado do sistema antigo: a imagem usa o endereço longo, idêntica à já impressa. Os dois links levam ao mesmo destino.</p>
<?php endif; ?>
    </section>

    <section class="cartao">
      <h2>Destino</h2>
      <form method="post" class="formulario">
        <?= campo_csrf() ?>
        <input type="hidden" name="acao" value="salvar">
        <label>Alias <input value="<?= h($alias) ?>" disabled><small>O alias não pode ser alterado.</small></label>
        <label>Destino <input type="url" name="url" value="<?= h($qr['url']) ?>" required></label>
        <label>Observações <textarea name="obs" rows="3" maxlength="500"><?= h($qr['obs'] ?? '') ?></textarea></label>
        <button class="botao" type="submit">Salvar</button>
      </form>
      <p class="legenda">Criado em <?= quando($qr['criado_em'] ?? null) ?> por <?= h($qr['criado_por'] ?? '—') ?>. Última alteração em <?= quando($qr['alterado_em'] ?? null) ?> por <?= h($qr['alterado_por'] ?? '—') ?>.</p>
    </section>

    <section class="cartao">
      <h2>Histórico</h2>
<?php if (!$eventos): ?>
      <p class="vazio">Sem alterações registradas.</p>
<?php else: ?>
      <ul class="historico">
<?php foreach ($eventos as $ev): $d = $ev['detalhes'] ?? []; ?>
        <li><span class="legenda"><?= quando($ev['quando'] ?? null) ?> · <?= h($ev['usuario'] ?? '') ?></span>
          <strong><?= h($rotulos[$ev['acao'] ?? ''] ?? ($ev['acao'] ?? '')) ?></strong>
<?php if (($ev['acao'] ?? '') === 'destino'): ?>
          <span class="mudanca"><s><?= h((string) ($d['antes'] ?? '')) ?></s> → <?= h((string) ($d['depois'] ?? '')) ?></span>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </section>

    <section class="cartao cartao--perigo">
      <h2>Apagar</h2>
      <p>Apagar é definitivo: os QR codes impressos com este alias passam a levar ao destino padrão. Se for só por um tempo, use <strong>Pausar</strong>.</p>
      <form method="post" class="formulario" data-confirmar="Apagar o QR code &quot;<?= h($alias) ?>&quot; de vez?">
        <?= campo_csrf() ?>
        <input type="hidden" name="acao" value="apagar">
        <label>Para confirmar, digite o alias: <strong><?= h($alias) ?></strong>
          <input name="confirmar_alias" required autocomplete="off"></label>
        <button class="botao botao--perigo" type="submit"><?= icone('lixeira') ?> Apagar de vez</button>
      </form>
    </section>
  </div>
</div>
<?php rodape(); ?>
