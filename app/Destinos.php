<?php
/**
 * Os QR codes e seus destinos (dados/destinos.php). Só o painel grava aqui; as leituras nunca.
 *
 * Cada QR code, pela chave = alias (o nome que vai no endereço, imutável depois de criado):
 *   url           destino atual
 *   status        "ativo" ou "pausado" (pausado leva ao destino padrão, sem perder o alias)
 *   obs           observações livres
 *   formato       "antigo" (QR com /qrcode/index.php?target=alias, como os já impressos) ou
 *                 "curto" (QR com /alias)
 *   anteriores    leituras contadas pelo sistema antigo, antes da importação
 *   criado_em, criado_por, alterado_em, alterado_por
 */

final class Destinos
{
    /** Nomes que não podem virar alias, para não colidir com pastas e arquivos do sistema. */
    public const RESERVADOS = ['admin', 'app', 'dados', 'importar', 'qrcode', 'ferramentas', 'assets', 'index', 'index.php', 'robots.txt', 'favicon.ico'];

    public static function todos(): array
    {
        return Armazem::ler('destinos') ?? [];
    }

    public static function obter(string $alias): ?array
    {
        return self::todos()[$alias] ?? null;
    }

    /**
     * Alias aceito para QR codes NOVOS: letras minúsculas, números e hífen, de 2 a 60 caracteres.
     * (Os importados do sistema antigo mantêm o nome original, com maiúsculas e espaços.)
     */
    public static function aliasValido(string $alias): bool
    {
        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,58}[a-z0-9])?$/', $alias) === 1
            && !in_array($alias, self::RESERVADOS, true);
    }

    public static function urlValida(string $url): bool
    {
        return preg_match('~^https?://~i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /** Cria um QR code novo (sempre no formato curto). */
    public static function criar(string $alias, string $url, string $obs, string $usuario): void
    {
        if (!self::aliasValido($alias)) {
            throw new RuntimeException('Alias inválido: use de 2 a 60 letras minúsculas, números ou hífen, sem começar ou terminar com hífen.');
        }
        if (!self::urlValida($url)) {
            throw new RuntimeException('Destino inválido: informe um endereço completo, começando com https://');
        }
        $agora = date('Y-m-d H:i:s');
        Armazem::atualizar('destinos', function (array $todos) use ($alias, $url, $obs, $usuario, $agora): array {
            if (isset($todos[$alias])) {
                throw new RuntimeException('Já existe um QR code com esse alias.');
            }
            $todos[$alias] = [
                'url' => $url, 'status' => 'ativo', 'obs' => $obs, 'formato' => 'curto', 'anteriores' => 0,
                'criado_em' => $agora, 'criado_por' => $usuario, 'alterado_em' => $agora, 'alterado_por' => $usuario,
            ];

            return $todos;
        });
        Historico::registrar($usuario, 'criar', $alias, ['url' => $url]);
    }

    /**
     * Altera destino, observações ou status de um QR code. O alias nunca muda.
     * Cada campo alterado vai para o histórico com o valor anterior.
     */
    public static function alterar(string $alias, array $campos, string $usuario): void
    {
        $campos = array_intersect_key($campos, ['url' => 1, 'obs' => 1, 'status' => 1]);
        if (isset($campos['url']) && !self::urlValida($campos['url'])) {
            throw new RuntimeException('Destino inválido: informe um endereço completo, começando com https://');
        }
        if (isset($campos['status']) && !in_array($campos['status'], ['ativo', 'pausado'], true)) {
            throw new RuntimeException('Situação inválida.');
        }
        $antes = null;
        Armazem::atualizar('destinos', function (array $todos) use ($alias, $campos, $usuario, &$antes): ?array {
            if (!isset($todos[$alias])) {
                throw new RuntimeException('QR code não encontrado.');
            }
            $antes = $todos[$alias];
            $todos[$alias] = array_merge($todos[$alias], $campos, ['alterado_em' => date('Y-m-d H:i:s'), 'alterado_por' => $usuario]);

            return $todos;
        });
        foreach ($campos as $campo => $valor) {
            if (($antes[$campo] ?? null) !== $valor) {
                $acao = $campo === 'url' ? 'destino' : ($campo === 'status' ? ($valor === 'pausado' ? 'pausar' : 'reativar') : 'observacao');
                Historico::registrar($usuario, $acao, $alias, ['antes' => $antes[$campo] ?? null, 'depois' => $valor]);
            }
        }
    }

    /** Apaga de vez (o painel pede confirmação dupla antes). As leituras antigas ficam nos registros. */
    public static function apagar(string $alias, string $usuario): void
    {
        $antes = null;
        Armazem::atualizar('destinos', function (array $todos) use ($alias, &$antes): ?array {
            if (!isset($todos[$alias])) {
                return null;
            }
            $antes = $todos[$alias];
            unset($todos[$alias]);

            return $todos;
        });
        if ($antes) {
            Historico::registrar($usuario, 'apagar', $alias, ['url' => $antes['url'] ?? null]);
        }
    }

    /** Endereço que vai dentro do QR code. */
    public static function endereco(string $alias, array $qr): string
    {
        $base = Ajustes::base();

        return ($qr['formato'] ?? 'curto') === 'antigo'
            ? $base . '/qrcode/index.php?target=' . rawurlencode($alias)
            : $base . '/' . rawurlencode($alias);
    }
}
