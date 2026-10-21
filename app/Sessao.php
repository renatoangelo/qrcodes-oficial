<?php
/**
 * Sessão do painel: login, expiração, código anti-falsificação (CSRF) e bloqueio por tentativas.
 *
 * - Cookie só HTTP (inacessível ao JavaScript), restrito à pasta /admin, SameSite=Lax e Secure em HTTPS.
 * - Novo identificador de sessão a cada login (impede sequestro de sessão).
 * - Sessão expira depois de 2 horas sem uso.
 * - Depois de 5 senhas erradas em 15 minutos, para o mesmo login ou o mesmo endereço, o login fica
 *   bloqueado por 15 minutos. O endereço é guardado só como impressão cifrada e temporária.
 */

final class Sessao
{
    private const EXPIRA_SEGUNDOS  = 7200;
    private const MAX_TENTATIVAS   = 5;
    private const JANELA_SEGUNDOS  = 900;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $caminho = parse_url(Ajustes::base(), PHP_URL_PATH) ?: '';
        session_name('qrpainel');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => rtrim($caminho, '/') . '/admin/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();

        if (isset($_SESSION['login']) && (time() - (int) ($_SESSION['visto'] ?? 0)) > self::EXPIRA_SEGUNDOS) {
            self::encerrar();
            session_start();
            avisar('A sessão expirou. Entre de novo.', 'erro');
        }
        $_SESSION['visto'] = time();
    }

    /** Usuário logado, ou null. Confere a cada página se ele continua existindo e ativo. */
    public static function usuario(): ?array
    {
        $login = $_SESSION['login'] ?? null;
        if (!is_string($login)) {
            return null;
        }
        $usuario = Usuarios::obter($login);
        if (!$usuario || empty($usuario['ativo'])) {
            self::encerrar();

            return null;
        }

        return ['login' => $login] + $usuario;
    }

    /** Exige login (e, se pedido, perfil de administrador). Sem usuários ainda, leva à instalação. */
    public static function exigir(bool $soAdmin = false): array
    {
        if (!Usuarios::existeAlgum()) {
            ir(painel('instalar.php'));
        }
        $usuario = self::usuario();
        if (!$usuario) {
            ir(painel('login.php'));
        }
        if ($soAdmin && ($usuario['perfil'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Acesso restrito a administradores.');
        }

        return $usuario;
    }

    public static function entrar(string $login): void
    {
        session_regenerate_id(true);
        $_SESSION['login'] = $login;
        $_SESSION['visto'] = time();
        unset($_SESSION['csrf']);
    }

    public static function encerrar(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600] + array_intersect_key($p, array_flip(['path', 'domain', 'secure', 'httponly', 'samesite'])));
            session_destroy();
        }
    }

    public static function csrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    /** Recusa qualquer envio de formulário sem o código anti-falsificação correto. */
    public static function conferirCsrf(): void
    {
        $enviado = $_POST['csrf'] ?? '';
        if (!is_string($enviado) || !hash_equals(self::csrf(), $enviado)) {
            http_response_code(400);
            exit('O formulário expirou. Volte, recarregue a página e tente de novo.');
        }
    }

    // ---------------------------------------------------------------- bloqueio por tentativas

    /** Segundos de bloqueio restantes para este login/endereço (0 = liberado). */
    public static function bloqueio(string $login): int
    {
        $agora = time();
        $tentativas = self::tentativasRecentes();
        $restante = 0;
        foreach ([self::chave('l', $login), self::chave('e', self::endereco())] as $chave) {
            $lista = $tentativas[$chave] ?? [];
            if (count($lista) >= self::MAX_TENTATIVAS) {
                $restante = max($restante, (int) min($lista) + self::JANELA_SEGUNDOS - $agora);
            }
        }

        return max(0, $restante);
    }

    public static function registrarFalha(string $login): void
    {
        $chaves = [self::chave('l', $login), self::chave('e', self::endereco())];
        Armazem::atualizar('tentativas', function (array $todas) use ($chaves): array {
            $todas = self::limparVelhas($todas);
            foreach ($chaves as $chave) {
                $todas[$chave][] = time();
            }

            return $todas;
        });
    }

    public static function limparFalhas(string $login): void
    {
        $chaves = [self::chave('l', $login), self::chave('e', self::endereco())];
        Armazem::atualizar('tentativas', function (array $todas) use ($chaves): array {
            foreach ($chaves as $chave) {
                unset($todas[$chave]);
            }

            return self::limparVelhas($todas);
        });
    }

    private static function tentativasRecentes(): array
    {
        try {
            return self::limparVelhas(Armazem::ler('tentativas') ?? []);
        } catch (RuntimeException) {
            return [];
        }
    }

    private static function limparVelhas(array $todas): array
    {
        $limite = time() - self::JANELA_SEGUNDOS;
        foreach ($todas as $chave => $lista) {
            $todas[$chave] = array_values(array_filter((array) $lista, fn ($t): bool => (int) $t > $limite));
            if (!$todas[$chave]) {
                unset($todas[$chave]);
            }
        }

        return $todas;
    }

    private static function endereco(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    /** Impressão cifrada (não reversível) do login ou do endereço, com o segredo da instalação. */
    private static function chave(string $tipo, string $valor): string
    {
        return $tipo . substr(hash_hmac('sha256', mb_strtolower($valor), Ajustes::segredo()), 0, 32);
    }
}
