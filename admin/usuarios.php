<?php
/**
 * Usuários (só administradores): cadastrar, mudar perfil, desativar/reativar, definir nova senha e
 * remover. O último administrador ativo não pode ser desativado, rebaixado nem removido.
 */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir(true);

$erro = null;
$novo = ['login' => '', 'nome' => '', 'perfil' => 'usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $acao = (string) ($_POST['acao'] ?? '');
    $alvo = (string) ($_POST['login'] ?? '');
    try {
        if ($acao === 'criar') {
            $novo = ['login' => mb_strtolower(trim($alvo)), 'nome' => trim((string) ($_POST['nome'] ?? '')), 'perfil' => (string) ($_POST['perfil'] ?? 'usuario')];
            $senha = (string) ($_POST['senha'] ?? '');
            if (!Usuarios::loginValido($novo['login'])) {
                throw new RuntimeException('Login: de 3 a 40 caracteres, só letras minúsculas, números, ponto, hífen e sublinhado.');
            }
            if ($problema = Usuarios::problemaNaSenha($senha, $novo['login'])) {
                throw new RuntimeException($problema);
            }
            Usuarios::criar($novo['login'], $novo['nome'] ?: $novo['login'], $senha, $novo['perfil'], $usuario['login']);
            avisar('Usuário "' . $novo['login'] . '" criado. Passe a senha a ele por um canal seguro.');
            ir(painel('usuarios.php'));
        }

        $existente = Usuarios::obter($alvo);
        if (!$existente) {
            throw new RuntimeException('Usuário não encontrado.');
        }
        $ehUltimoAdmin = ($existente['perfil'] ?? '') === 'admin' && !empty($existente['ativo']) && Usuarios::adminsAtivos() <= 1;

        if ($acao === 'perfil') {
            $perfil = (string) ($_POST['perfil'] ?? '');
            if (!isset(Usuarios::PERFIS[$perfil])) {
                throw new RuntimeException('Perfil inválido.');
            }
            if ($ehUltimoAdmin && $perfil !== 'admin') {
                throw new RuntimeException('Este é o único administrador: crie outro antes de mudar o perfil dele.');
            }
            Usuarios::alterar($alvo, ['perfil' => $perfil], $usuario['login'], 'usuario-perfil');
            avisar('Perfil atualizado.');
        } elseif ($acao === 'ativar' || $acao === 'desativar') {
            if ($acao === 'desativar' && ($ehUltimoAdmin || $alvo === $usuario['login'])) {
                throw new RuntimeException($alvo === $usuario['login'] ? 'Você não pode desativar a si mesmo.' : 'Este é o único administrador ativo.');
            }
            Usuarios::alterar($alvo, ['ativo' => $acao === 'ativar'], $usuario['login'], 'usuario-' . $acao);
            avisar($acao === 'ativar' ? 'Usuário reativado.' : 'Usuário desativado: ele não consegue mais entrar.');
        } elseif ($acao === 'senha') {
            $senha = (string) ($_POST['senha'] ?? '');
            if ($problema = Usuarios::problemaNaSenha($senha, $alvo)) {
                throw new RuntimeException($problema);
            }
            Usuarios::alterar($alvo, ['senha' => $senha], $usuario['login'], 'usuario-senha');
            avisar('Nova senha definida para "' . $alvo . '".');
        } elseif ($acao === 'remover') {
            if ($ehUltimoAdmin || $alvo === $usuario['login']) {
                throw new RuntimeException($alvo === $usuario['login'] ? 'Você não pode remover a si mesmo.' : 'Este é o único administrador ativo.');
            }
            Usuarios::remover($alvo, $usuario['login']);
            avisar('Usuário "' . $alvo . '" removido.');
        }
        ir(painel('usuarios.php'));
    } catch (RuntimeException $e) {
        $erro = $e->getMessage();
    }
}

$todos = Usuarios::todos();
ksort($todos);
topo('Usuários', $usuario, 'usuarios.php');
?>
<div class="cabecalho"><h1>Usuários</h1></div>
<?php if ($erro): ?>
<p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>

<div class="tabela-rolagem">
<table class="tabela">
  <thead><tr><th>Usuário</th><th>Perfil</th><th>Situação</th><th>Último acesso</th><th><span class="so-leitor">Ações</span></th></tr></thead>
  <tbody>
<?php foreach ($todos as $login => $u): $login = (string) $login; ?>
    <tr>
      <td><span class="forte"><?= h($u['nome'] ?? $login) ?></span><br><span class="legenda"><?= h($login) ?></span></td>
      <td>
        <form method="post" class="em-linha">
          <?= campo_csrf() ?><input type="hidden" name="acao" value="perfil"><input type="hidden" name="login" value="<?= h($login) ?>">
          <select name="perfil" aria-label="Perfil de <?= h($login) ?>" data-enviar-ao-mudar>
<?php foreach (Usuarios::PERFIS as $chave => $rotulo): ?>
            <option value="<?= h($chave) ?>"<?= ($u['perfil'] ?? '') === $chave ? ' selected' : '' ?>><?= h($rotulo) ?></option>
<?php endforeach; ?>
          </select>
        </form>
      </td>
      <td><span class="situacao situacao--<?= !empty($u['ativo']) ? 'ativo' : 'pausado' ?>"><?= !empty($u['ativo']) ? 'Ativo' : 'Desativado' ?></span></td>
      <td><?= quando($u['ultimo_acesso'] ?? null) ?></td>
      <td class="acoes">
        <details class="menu-acoes">
          <summary class="botao botao--secundario">Ações</summary>
          <div class="menu-acoes__painel">
            <form method="post" class="formulario">
              <?= campo_csrf() ?><input type="hidden" name="acao" value="senha"><input type="hidden" name="login" value="<?= h($login) ?>">
              <label>Nova senha <input type="password" name="senha" minlength="10" required autocomplete="new-password"></label>
              <button class="botao botao--secundario" type="submit">Definir senha</button>
            </form>
<?php if ($login !== $usuario['login']): ?>
            <form method="post">
              <?= campo_csrf() ?><input type="hidden" name="acao" value="<?= !empty($u['ativo']) ? 'desativar' : 'ativar' ?>"><input type="hidden" name="login" value="<?= h($login) ?>">
              <button class="botao botao--secundario" type="submit"><?= !empty($u['ativo']) ? 'Desativar' : 'Reativar' ?></button>
            </form>
            <form method="post" data-confirmar="Remover o usuário &quot;<?= h($login) ?>&quot;?">
              <?= campo_csrf() ?><input type="hidden" name="acao" value="remover"><input type="hidden" name="login" value="<?= h($login) ?>">
              <button class="botao botao--perigo" type="submit"><?= icone('lixeira') ?> Remover</button>
            </form>
<?php endif; ?>
          </div>
        </details>
      </td>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
</div>

<section class="cartao cartao--medio">
  <h2>Novo usuário</h2>
  <form method="post" class="formulario">
    <?= campo_csrf() ?><input type="hidden" name="acao" value="criar">
    <label>Nome <input name="nome" value="<?= h($novo['nome']) ?>"></label>
    <label>Login <input name="login" value="<?= h($novo['login']) ?>" required pattern="[a-z0-9._-]{3,40}" autocomplete="off"></label>
    <label>Senha inicial (mínimo 10 caracteres) <input type="password" name="senha" required minlength="10" autocomplete="new-password"></label>
    <label>Perfil
      <select name="perfil">
<?php foreach (Usuarios::PERFIS as $chave => $rotulo): ?>
        <option value="<?= h($chave) ?>"<?= $novo['perfil'] === $chave ? ' selected' : '' ?>><?= h($rotulo) ?></option>
<?php endforeach; ?>
      </select>
      <small>Administrador também cadastra usuários e muda os ajustes. Usuário cuida dos QR codes.</small></label>
    <button class="botao" type="submit">Criar usuário</button>
  </form>
</section>
<?php rodape(); ?>
