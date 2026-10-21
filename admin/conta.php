<?php
/** Minha conta: trocar nome e senha (pedindo a senha atual). */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir();

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $atual = (string) ($_POST['atual'] ?? '');
    $nova = (string) ($_POST['nova'] ?? '');
    $nome = trim((string) ($_POST['nome'] ?? ''));

    if (!password_verify($atual, (string) $usuario['senha'])) {
        $erro = 'A senha atual não confere.';
    } elseif ($nova !== '' && ($problema = Usuarios::problemaNaSenha($nova, $usuario['login']))) {
        $erro = $problema;
    } elseif ($nova !== '' && $nova !== (string) ($_POST['nova2'] ?? '')) {
        $erro = 'As duas senhas novas não são iguais.';
    } else {
        $campos = ['nome' => $nome ?: $usuario['login']];
        if ($nova !== '') {
            $campos['senha'] = $nova;
        }
        Usuarios::alterar($usuario['login'], $campos, $usuario['login'], 'conta');
        avisar($nova !== '' ? 'Senha alterada.' : 'Dados atualizados.');
        ir(painel('conta.php'));
    }
}

topo('Minha conta', $usuario);
?>
<section class="cartao cartao--estreito">
  <h1>Minha conta</h1>
  <p class="legenda">Login: <strong><?= h($usuario['login']) ?></strong> · <?= h(Usuarios::PERFIS[$usuario['perfil']] ?? '') ?></p>
<?php if ($erro): ?>
  <p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>
  <form method="post" class="formulario">
    <?= campo_csrf() ?>
    <label>Nome <input name="nome" value="<?= h($usuario['nome'] ?? '') ?>" autocomplete="name"></label>
    <label>Nova senha <input type="password" name="nova" minlength="10" autocomplete="new-password"><small>Deixe em branco para manter a atual.</small></label>
    <label>Repita a nova senha <input type="password" name="nova2" minlength="10" autocomplete="new-password"></label>
    <label>Senha atual <input type="password" name="atual" required autocomplete="current-password"><small>Obrigatória para salvar.</small></label>
    <button class="botao" type="submit">Salvar</button>
  </form>
</section>
<?php rodape(); ?>
