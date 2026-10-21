<?php
/**
 * Primeira execução: cria o primeiro administrador.
 *
 * Para ninguém criar o administrador antes de você, a tela pede um código de instalação. Ele é
 * gerado na primeira visita e fica no arquivo dados/codigo-de-instalacao.php (abra pelo
 * Gerenciador de Arquivos do servidor). No localhost o código aparece na própria tela.
 * Depois que existe um usuário, esta página não faz mais nada.
 */
require __DIR__ . '/_inicio.php';

if (Usuarios::existeAlgum()) {
    ir(painel('login.php'));
}

Armazem::prepararPasta();
$arquivoCodigo = Armazem::caminho('codigo-de-instalacao');
if (!is_file($arquivoCodigo)) {
    $novo = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
    file_put_contents($arquivoCodigo, "<?php exit; ?>\n" . $novo . "\n");
}
$codigo = trim((string) (file($arquivoCodigo)[1] ?? ''));
$host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '');
$local = in_array($host, ['localhost', '127.0.0.1'], true);

$erro = null;
$campos = ['nome' => '', 'login' => '', 'base' => Ajustes::base(), 'padrao' => Ajustes::obter('padrao', 'https://')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $campos = array_map(fn ($v) => trim((string) $v), array_intersect_key($_POST, $campos)) + $campos;
    $senha = (string) ($_POST['senha'] ?? '');

    if (!hash_equals($codigo, strtoupper(trim((string) ($_POST['codigo'] ?? ''))))) {
        $erro = 'Código de instalação incorreto.';
    } elseif (!Usuarios::loginValido($campos['login'])) {
        $erro = 'Login: de 3 a 40 caracteres, só letras minúsculas, números, ponto, hífen e sublinhado.';
    } elseif ($problema = Usuarios::problemaNaSenha($senha, $campos['login'])) {
        $erro = $problema;
    } elseif ($senha !== (string) ($_POST['senha2'] ?? '')) {
        $erro = 'As duas senhas não são iguais.';
    } elseif (!Destinos::urlValida($campos['padrao'])) {
        $erro = 'Informe um destino padrão válido, começando com https://';
    } elseif (!Destinos::urlValida($campos['base'])) {
        $erro = 'Endereço do sistema inválido.';
    } else {
        Usuarios::criar($campos['login'], $campos['nome'] ?: $campos['login'], $senha, 'admin', 'instalação');
        Ajustes::salvar(['base' => rtrim($campos['base'], '/'), 'padrao' => $campos['padrao'], 'nome' => Ajustes::obter('nome', 'QR codes')], $campos['login']);
        @unlink($arquivoCodigo);
        Sessao::entrar($campos['login']);
        avisar('Instalação concluída. Bem-vindo!');
        ir(painel('index.php'));
    }
}

topo('Instalação');
?>
<section class="cartao cartao--estreito">
  <h1>Instalação</h1>
  <p>Crie o primeiro administrador. Ele poderá cadastrar outros usuários depois.</p>
<?php if ($local): ?>
  <p class="aviso aviso--info">Código de instalação (aparece aqui só no localhost): <strong><?= h($codigo) ?></strong></p>
<?php else: ?>
  <p class="aviso aviso--info">O código de instalação está no arquivo <code>dados/codigo-de-instalacao.php</code>, dentro da pasta do sistema no servidor. Abra pelo Gerenciador de Arquivos: é a segunda linha.</p>
<?php endif; ?>
<?php if ($erro): ?>
  <p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>
  <form method="post" class="formulario">
    <?= campo_csrf() ?>
    <label>Código de instalação <input name="codigo" required autocomplete="off" autocapitalize="characters"></label>
    <label>Seu nome <input name="nome" value="<?= h($campos['nome']) ?>" autocomplete="name"></label>
    <label>Login <input name="login" value="<?= h($campos['login']) ?>" required pattern="[a-z0-9._-]{3,40}" autocomplete="username"></label>
    <label>Senha (mínimo 10 caracteres) <input type="password" name="senha" required minlength="10" autocomplete="new-password"></label>
    <label>Repita a senha <input type="password" name="senha2" required minlength="10" autocomplete="new-password"></label>
    <label>Endereço deste sistema <input name="base" value="<?= h($campos['base']) ?>" required>
      <small>Vai dentro dos QR codes. Confira se é o endereço público, por exemplo https://qrcode.seudominio.com.br</small></label>
    <label>Destino padrão <input name="padrao" value="<?= h($campos['padrao']) ?>" required>
      <small>Para onde vai quem ler um QR code pausado ou inexistente.</small></label>
    <button class="botao" type="submit">Concluir instalação</button>
  </form>
</section>
<?php rodape(); ?>
