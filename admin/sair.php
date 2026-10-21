<?php
/** Sair: só por formulário (POST com código anti-falsificação), para um link externo não deslogar ninguém. */
require __DIR__ . '/_inicio.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Sessao::conferirCsrf();
    Sessao::encerrar();
}
ir(painel('login.php'));
