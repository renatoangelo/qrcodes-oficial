<?php
/** Novo QR code. O alias é definitivo: depois de criado, não pode ser alterado. */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir();

$erro = null;
$campos = ['alias' => '', 'url' => 'https://', 'obs' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $campos = [
        'alias' => mb_strtolower(trim((string) ($_POST['alias'] ?? ''))),
        'url'   => trim((string) ($_POST['url'] ?? '')),
        'obs'   => trim((string) ($_POST['obs'] ?? '')),
    ];
    try {
        Destinos::criar($campos['alias'], $campos['url'], mb_substr($campos['obs'], 0, 500), $usuario['login']);
        avisar('QR code criado.');
        ir(painel('qrcode.php', ['alias' => $campos['alias']]));
    } catch (RuntimeException $e) {
        $erro = $e->getMessage();
    }
}

topo('Novo QR code', $usuario, 'qrcodes.php');
?>
<p class="migalha"><a href="<?= h(painel('qrcodes.php')) ?>">QR codes</a> › Novo</p>
<section class="cartao cartao--medio">
  <h1>Novo QR code</h1>
<?php if ($erro): ?>
  <p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>
  <form method="post" class="formulario">
    <?= campo_csrf() ?>
    <label>Alias <input name="alias" value="<?= h($campos['alias']) ?>" required pattern="[a-z0-9][a-z0-9-]{0,58}[a-z0-9]" maxlength="60" autocomplete="off" data-previa-alias>
      <small>Vai no endereço: <strong><?= h(Ajustes::base()) ?>/<span data-previa><?= h($campos['alias'] ?: 'alias') ?></span></strong>. Só letras minúsculas, números e hífen. <strong>Não pode ser alterado depois.</strong></small></label>
    <label>Destino <input type="url" name="url" value="<?= h($campos['url']) ?>" required>
      <small>Para onde vai quem ler o QR code. Este pode ser trocado a qualquer momento.</small></label>
    <label>Observações <textarea name="obs" rows="3" maxlength="500" placeholder="Ex.: cartão de visitas, 1.000 unidades impressas em outubro"><?= h($campos['obs']) ?></textarea></label>
    <div class="botoes">
      <button class="botao" type="submit">Criar QR code</button>
      <a class="botao botao--secundario" href="<?= h(painel('qrcodes.php')) ?>">Cancelar</a>
    </div>
  </form>
</section>
<?php rodape(); ?>
