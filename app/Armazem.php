<?php
/**
 * Armazenamento em arquivos, sem banco de dados.
 *
 * Regras que impedem a perda de dados que aconteceu no sistema antigo (2026-10-20):
 * - Toda leitura+gravação de um arquivo de dados acontece com trava exclusiva (um acesso por vez).
 * - A gravação é atômica: vai para um arquivo temporário e só então substitui o original.
 * - Se o arquivo existir mas não puder ser lido como dados válidos, NADA é gravado por cima.
 * - Antes de cada gravação, a versão anterior fica em <arquivo>.bak.
 * - As leituras de QR code NUNCA regravam os destinos: elas só acrescentam linhas ao arquivo do
 *   mês (Armazem::acrescentar), que é o que torna o sistema seguro com muitos acessos simultâneos.
 *
 * Proteção: todo arquivo de dados é um .php que começa com "<?php exit; ?>". Mesmo num servidor
 * que ignore o .htaccess, quem tentar abrir o arquivo pelo navegador recebe uma página em branco.
 */

final class Armazem
{
    private const CABECALHO = "<?php exit; ?>\n";

    /** Caminho completo de um arquivo de dados. Ex.: caminho('destinos') → dados/destinos.php */
    public static function caminho(string $nome): string
    {
        return QR_DADOS . '/' . $nome . '.php';
    }

    /** Garante que a pasta de dados exista e esteja protegida. */
    public static function prepararPasta(string $pasta = QR_DADOS): void
    {
        if (!is_dir($pasta) && !@mkdir($pasta, 0750, true) && !is_dir($pasta)) {
            throw new RuntimeException("Não foi possível criar a pasta de dados: {$pasta}");
        }
        if (!is_file($pasta . '/.htaccess')) {
            @file_put_contents($pasta . '/.htaccess', "Require all denied\nDeny from all\n");
        }
        if (!is_file($pasta . '/index.php')) {
            @file_put_contents($pasta . '/index.php', "<?php http_response_code(404);\n");
        }
    }

    /** Lê um arquivo JSON de dados. null = não existe; lança exceção se existir e estiver corrompido. */
    public static function ler(string $nome): ?array
    {
        $arquivo = self::caminho($nome);
        if (!is_file($arquivo)) {
            return null;
        }

        return self::decodificar((string) file_get_contents($arquivo), $arquivo);
    }

    /**
     * Lê, altera e grava um arquivo JSON de dados com trava. $alterar recebe os dados atuais
     * ([] se o arquivo ainda não existe) e devolve os novos; devolver null cancela a gravação.
     */
    public static function atualizar(string $nome, callable $alterar): array
    {
        self::prepararPasta();
        $arquivo = self::caminho($nome);
        $trava = self::travar($nome);

        try {
            $atuais = is_file($arquivo) ? self::decodificar((string) file_get_contents($arquivo), $arquivo) : [];
            $novos = $alterar($atuais);
            if (!is_array($novos)) {
                return $atuais;
            }

            $json = json_encode($novos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (is_file($arquivo)) {
                @copy($arquivo, $arquivo . '.bak');
            }
            $temporario = $arquivo . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (file_put_contents($temporario, self::CABECALHO . $json, LOCK_EX) === false || !rename($temporario, $arquivo)) {
                @unlink($temporario);
                throw new RuntimeException("Não foi possível gravar {$arquivo}");
            }

            return $novos;
        } finally {
            flock($trava, LOCK_UN);
            fclose($trava);
        }
    }

    /**
     * Acrescenta uma linha ao fim de um arquivo de registros (leituras, histórico), com trava.
     * Nunca reescreve o que já existe.
     */
    public static function acrescentar(string $nome, string $linha): void
    {
        self::prepararPasta(dirname(self::caminho($nome)));
        $arquivo = self::caminho($nome);
        $handle = @fopen($arquivo, 'ab');
        if ($handle === false) {
            error_log("qrcodes: não foi possível abrir {$arquivo} para registro");
            return;
        }
        if (flock($handle, LOCK_EX)) {
            clearstatcache(true, $arquivo);
            if (filesize($arquivo) === 0) {
                fwrite($handle, self::CABECALHO);
            }
            fwrite($handle, str_replace(["\r", "\n"], ' ', $linha) . "\n");
            fflush($handle);
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }

    /** Lê as linhas de um arquivo de registros (sem o cabeçalho de proteção). */
    public static function linhas(string $nome): array
    {
        $arquivo = self::caminho($nome);
        if (!is_file($arquivo)) {
            return [];
        }
        $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if ($linhas && str_starts_with($linhas[0], '<?php')) {
            array_shift($linhas);
        }

        return $linhas;
    }

    private static function decodificar(string $conteudo, string $arquivo): array
    {
        if (str_starts_with($conteudo, self::CABECALHO)) {
            $conteudo = substr($conteudo, strlen(self::CABECALHO));
        }
        $dados = json_decode($conteudo, true);
        if (!is_array($dados)) {
            throw new RuntimeException("Arquivo de dados ilegível, nada foi alterado: {$arquivo}");
        }

        return $dados;
    }

    /** @return resource */
    private static function travar(string $nome)
    {
        $trava = fopen(QR_DADOS . '/.' . str_replace('/', '-', $nome) . '.lock', 'c');
        if ($trava === false || !flock($trava, LOCK_EX)) {
            throw new RuntimeException("Não foi possível travar os dados ({$nome})");
        }

        return $trava;
    }
}
