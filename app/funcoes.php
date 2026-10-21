<?php
/**
 * Funções pequenas usadas pelo painel.
 */

/** Escapa texto para HTML. Tudo o que vem de dados ou do usuário passa por aqui. */
function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Endereço de uma página do painel, relativo à instalação. Ex.: painel('qrcode.php', ['alias' => 'x']) */
function painel(string $pagina = '', array $parametros = []): string
{
    $url = Ajustes::base() . '/admin/' . $pagina;

    return $parametros ? $url . '?' . http_build_query($parametros) : $url;
}

/** Redireciona e encerra. */
function ir(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Mensagem que aparece uma vez na próxima página (sucesso ou erro). */
function avisar(string $texto, string $tipo = 'ok'): void
{
    $_SESSION['avisos'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function avisos(): array
{
    $lista = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);

    return $lista;
}

/** Campo oculto com o código anti-falsificação, obrigatório em todo formulário que altera algo. */
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . h(Sessao::csrf()) . '">';
}

/** Data e hora no formato brasileiro. */
function quando(?string $data): string
{
    if (!$data) {
        return '—';
    }
    $ts = strtotime($data);

    return $ts ? date('d/m/Y H:i', $ts) : h($data);
}

function numero(int $n): string
{
    return number_format($n, 0, ',', '.');
}

/** Ícone SVG (traço), dos que o painel usa. */
function icone(string $nome): string
{
    static $tracos = [
        'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'copiar'    => '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'editar'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'qr'        => '<rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/>',
        'baixar'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'lixeira'   => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'pausar'    => '<rect width="4" height="16" x="6" y="4" rx="1"/><rect width="4" height="16" x="14" y="4" rx="1"/>',
        'ativar'    => '<path d="m6 3 14 9-14 9V3z"/>',
        'mais'      => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'sair'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'abrir'     => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'busca'     => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    ];

    return '<svg class="icone" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($tracos[$nome] ?? '') . '</svg>';
}
