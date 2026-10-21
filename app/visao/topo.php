<?php
/** Cabeçalho do painel. Recebe $titulo, $usuario (null nas telas de login/instalação) e $atual. */
$nomeInstalacao = Ajustes::obter('nome', 'QR codes');
$menu = [
    'index.php'     => 'Dashboard',
    'qrcodes.php'   => 'QR codes',
    'historico.php' => 'Histórico',
];
if ($usuario && ($usuario['perfil'] ?? '') === 'admin') {
    $menu['usuarios.php'] = 'Usuários';
    $menu['ajustes.php']  = 'Ajustes';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($titulo) ?> · <?= h($nomeInstalacao) ?></title>
  <link rel="stylesheet" href="<?= h(painel('assets/painel.css')) ?>?v=<?= h(QR_VERSAO) ?>">
  <script src="<?= h(painel('assets/painel.js')) ?>?v=<?= h(QR_VERSAO) ?>" defer></script>
</head>
<body class="<?= $usuario ? 'com-menu' : 'sem-menu' ?>">
<?php if ($usuario): ?>
<header class="topo">
  <a class="topo__marca" href="<?= h(painel('index.php')) ?>">
    <?= icone('qr') ?> <span><?= h($nomeInstalacao) ?></span>
  </a>
  <nav class="topo__menu" aria-label="Menu">
<?php foreach ($menu as $pagina => $rotulo): ?>
    <a href="<?= h(painel($pagina)) ?>"<?= $atual === $pagina ? ' aria-current="page"' : '' ?>><?= h($rotulo) ?></a>
<?php endforeach; ?>
  </nav>
  <div class="topo__usuario">
    <a href="<?= h(painel('conta.php')) ?>" title="Minha conta"><?= h($usuario['nome'] ?: $usuario['login']) ?></a>
    <form method="post" action="<?= h(painel('sair.php')) ?>">
      <?= campo_csrf() ?>
      <button class="botao botao--fantasma" type="submit" title="Sair"><?= icone('sair') ?><span class="so-leitor">Sair</span></button>
    </form>
  </div>
</header>
<?php endif; ?>
<main class="conteudo">
<?php foreach (avisos() as $aviso): ?>
  <p class="aviso aviso--<?= h($aviso['tipo']) ?>" role="status"><?= h($aviso['texto']) ?></p>
<?php endforeach; ?>
