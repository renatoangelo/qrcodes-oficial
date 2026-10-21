<?php
/**
 * Usuários do painel (dados/usuarios.php), pela chave = login.
 *   nome, senha (hash, nunca em texto), perfil ("admin" ou "usuario"), ativo (bool),
 *   criado_em, criado_por, ultimo_acesso
 * admin: tudo, inclusive usuários e ajustes. usuario: QR codes, dashboard e a própria senha.
 */

final class Usuarios
{
    public const PERFIS = ['admin' => 'Administrador', 'usuario' => 'Usuário'];

    public static function todos(): array
    {
        return Armazem::ler('usuarios') ?? [];
    }

    public static function obter(string $login): ?array
    {
        return self::todos()[$login] ?? null;
    }

    public static function existeAlgum(): bool
    {
        return self::todos() !== [];
    }

    public static function loginValido(string $login): bool
    {
        return preg_match('/^[a-z0-9._-]{3,40}$/', $login) === 1;
    }

    /** Regras mínimas de senha. Devolve a mensagem de erro, ou null se estiver boa. */
    public static function problemaNaSenha(string $senha, string $login = ''): ?string
    {
        if (mb_strlen($senha) < 10) {
            return 'A senha precisa ter pelo menos 10 caracteres.';
        }
        if ($login !== '' && stripos($senha, $login) !== false) {
            return 'A senha não pode conter o login.';
        }

        return null;
    }

    public static function criar(string $login, string $nome, string $senha, string $perfil, string $por): void
    {
        Armazem::atualizar('usuarios', function (array $todos) use ($login, $nome, $senha, $perfil, $por): array {
            if (isset($todos[$login])) {
                throw new RuntimeException('Já existe um usuário com esse login.');
            }
            $todos[$login] = [
                'nome'          => $nome,
                'senha'         => password_hash($senha, PASSWORD_DEFAULT),
                'perfil'        => isset(self::PERFIS[$perfil]) ? $perfil : 'usuario',
                'ativo'         => true,
                'criado_em'     => date('Y-m-d H:i:s'),
                'criado_por'    => $por,
                'ultimo_acesso' => null,
            ];

            return $todos;
        });
        Historico::registrar($por, 'usuario-criado', $login, ['perfil' => $perfil]);
    }

    /** Confere login e senha. Devolve o usuário ou null. */
    public static function autenticar(string $login, string $senha): ?array
    {
        $usuario = self::obter($login);
        // compara mesmo sem usuário, para o tempo de resposta não revelar se o login existe
        static $falso = null;
        $falso ??= password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $hash = $usuario['senha'] ?? $falso;
        if (!password_verify($senha, $hash) || !$usuario || empty($usuario['ativo'])) {
            return null;
        }
        Armazem::atualizar('usuarios', function (array $todos) use ($login, $senha): array {
            $todos[$login]['ultimo_acesso'] = date('Y-m-d H:i:s');
            if (password_needs_rehash($todos[$login]['senha'], PASSWORD_DEFAULT)) {
                $todos[$login]['senha'] = password_hash($senha, PASSWORD_DEFAULT);
            }

            return $todos;
        });

        return self::obter($login);
    }

    public static function alterar(string $login, array $campos, string $por, string $acao): void
    {
        Armazem::atualizar('usuarios', function (array $todos) use ($login, $campos): ?array {
            if (!isset($todos[$login])) {
                return null;
            }
            if (isset($campos['senha'])) {
                $campos['senha'] = password_hash($campos['senha'], PASSWORD_DEFAULT);
            }
            $todos[$login] = array_merge($todos[$login], $campos);

            return $todos;
        });
        Historico::registrar($por, $acao, $login, array_diff_key($campos, ['senha' => 1]));
    }

    public static function remover(string $login, string $por): void
    {
        Armazem::atualizar('usuarios', function (array $todos) use ($login): array {
            unset($todos[$login]);

            return $todos;
        });
        Historico::registrar($por, 'usuario-removido', $login);
    }

    /** Quantos administradores ativos existem (o último não pode ser removido nem desativado). */
    public static function adminsAtivos(): int
    {
        return count(array_filter(self::todos(), fn (array $u): bool => ($u['perfil'] ?? '') === 'admin' && !empty($u['ativo'])));
    }
}
