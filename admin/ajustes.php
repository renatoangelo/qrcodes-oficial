<?php
/** Ajustes da instalação (só administradores): nome, destino padrão, endereço e fuso. */
require __DIR__ . '/_inicio.php';
$usuario = Sessao::exigir(true);

$erro = null;
$campos = [
    'nome'   => Ajustes::obter('nome', 'QR codes'),
    'padrao' => Ajustes::obter('padrao', 'https://'),
    'base'   => Ajustes::base(),
    'fuso'   => Ajustes::obter('fuso', 'America/Sao_Paulo'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    $campos = array_map(fn ($v) => trim((string) $v), array_intersect_key($_POST, $campos)) + $campos;
    $campos['base'] = rtrim($campos['base'], '/');

    if ($campos['nome'] === '' || mb_strlen($campos['nome']) > 60) {
        $erro = 'Informe um nome de até 60 caracteres.';
    } elseif (!Destinos::urlValida($campos['padrao'])) {
        $erro = 'Destino padrão inválido: informe um endereço completo, começando com https://';
    } elseif (parse_url($campos['padrao'], PHP_URL_HOST) === parse_url($campos['base'], PHP_URL_HOST)) {
        $erro = 'O destino padrão não pode ser o próprio sistema de QR codes (criaria um laço).';
    } elseif (!Destinos::urlValida($campos['base'])) {
        $erro = 'Endereço do sistema inválido.';
    } elseif (!in_array($campos['fuso'], timezone_identifiers_list(), true)) {
        $erro = 'Fuso horário inválido.';
    } else {
        Ajustes::salvar($campos, $usuario['login']);
        avisar('Ajustes salvos.');
        ir(painel('ajustes.php'));
    }
}

topo('Ajustes', $usuario, 'ajustes.php');
?>
<section class="cartao cartao--medio">
  <h1>Ajustes</h1>
<?php if ($erro): ?>
  <p class="aviso aviso--erro" role="alert"><?= h($erro) ?></p>
<?php endif; ?>
  <form method="post" class="formulario">
    <?= campo_csrf() ?>
    <label>Nome do painel <input name="nome" value="<?= h($campos['nome']) ?>" required maxlength="60"></label>
    <label>Destino padrão <input type="url" name="padrao" value="<?= h($campos['padrao']) ?>" required>
      <small>Para onde vai quem ler um QR code pausado ou com um alias que não existe.</small></label>
    <label>Endereço deste sistema <input type="url" name="base" value="<?= h($campos['base']) ?>" required>
      <small>Vai dentro de todos os QR codes. <strong>Só mude se o domínio mudou:</strong> os já impressos continuam apontando para o endereço antigo.</small></label>
    <label>Fuso horário <input name="fuso" value="<?= h($campos['fuso']) ?>" required list="fusos">
      <datalist id="fusos"><option value="America/Sao_Paulo"><option value="America/Manaus"><option value="America/Cuiaba"><option value="America/Fortaleza"><option value="America/Recife"><option value="America/Belem"><option value="America/Rio_Branco"><option value="America/Noronha"></datalist></label>
    <button class="botao" type="submit">Salvar ajustes</button>
  </form>
</section>
<?php rodape(); ?>
