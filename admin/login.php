<?php
/** Login do painel, com bloqueio temporário depois de 5 senhas erradas. */
require __DIR__ . '/_inicio.php';

if (!Usuarios::existeAlgum()) {
    ir(painel('instalar.php'));
}
if (Sessao::usuario()) {
    ir(painel('index.php'));
}

$erro = null;
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $login = mb_strtolower(trim((string) ($_POST['login'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if (($espera = Sessao::bloqueio($login)) > 0) {
        $erro = 'Muitas tentativas. Tente de novo em ' . max(1, (int) ceil($espera / 60)) . ' minuto(s).';
    } elseif (Usuarios::autenticar($login, $senha)) {
        Sessao::limparFalhas($login);
        Sessao::entrar($login);
        Historico::registrar($login, 'login', $login);
        ir(painel('index.php'));
    } else {
        Sessao::registrarFalha($login);
        usleep(random_int(300000, 700000)); // atrasa quem tenta adivinhar
        $erro = 'Login ou senha incorretos.';
    }
}

topo('Entrar');
?>
<section class="cartao cartao--estreito">
  <h1><?= icone('qr') ?> <?= h(Ajustes::obter('nome', 'QR codes')) ?></h1>
<?php if ($erro): ?>
  <p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>
  <form method="post" class="formulario">
    <?= campo_csrf() ?>
    <label>Login <input name="login" value="<?= h($login) ?>" required autofocus autocomplete="username"></label>
    <label>Senha <input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="botao" type="submit">Entrar</button>
  </form>
</section>
<?php rodape(); ?>
