<?php
/**
 * Ponto de partida de todo o sistema. Carrega os módulos, define os caminhos e o fuso horário.
 *
 * Tudo é relativo a esta pasta: o sistema funciona em qualquer domínio ou subpasta, só
 * descompactando. Não há banco de dados: os dados ficam em dados/ (ver app/Armazem.php).
 */

declare(strict_types=1);

define('QR_RAIZ', dirname(__DIR__));
define('QR_APP', __DIR__);
define('QR_DADOS', QR_RAIZ . '/dados');
define('QR_IMPORTAR', QR_RAIZ . '/importar');
define('QR_VERSAO', '1.2.2');

require QR_APP . '/Armazem.php';
require QR_APP . '/Ajustes.php';
require QR_APP . '/Destinos.php';
require QR_APP . '/Acessos.php';
require QR_APP . '/Historico.php';
require QR_APP . '/Importador.php';
require QR_APP . '/Redirecionador.php';
require QR_APP . '/Usuarios.php';
require QR_APP . '/Sessao.php';
require QR_APP . '/QR.php';
require QR_APP . '/Estatisticas.php';
require QR_APP . '/funcoes.php';

// Fuso dos registros e do painel (ajustável no painel; padrão: horário de Brasília)
date_default_timezone_set(Ajustes::obter('fuso', 'America/Sao_Paulo'));

// Primeira execução com um contador.json do sistema antigo em importar/: importa uma única vez
Importador::seNecessario();
