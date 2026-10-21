<?php
/**
 * Importa o contador.json do sistema antigo, uma única vez.
 *
 * De onde lê, nesta ordem:
 *   1. importar/contador.json (cópia colocada à mão; depois de importada, é renomeada);
 *   2. qrcode/admin/contador.json, o arquivo do sistema antigo quando o novo é instalado na mesma pasta.
 *      Este nunca é alterado, para o sistema antigo poder voltar se for preciso.
 * Se ainda não houver dados/destinos.php, cada entrada vira um QR code com:
 *   - o MESMO alias (inclusive maiúsculas e espaços) e o mesmo destino;
 *   - formato "antigo", para o QR gerado ser idêntico ao já impresso;
 *   - as leituras do sistema antigo guardadas em "anteriores".
 * A entrada "default" também define o destino padrão da instalação.
 * A importação acontece uma vez só: depois que dados/destinos.php existe, nenhum dos dois é lido de novo.
 */

final class Importador
{
    public static function seNecessario(): void
    {
        if (is_file(Armazem::caminho('destinos'))) {
            return;
        }
        $copia = QR_IMPORTAR . '/contador.json';
        $origem = is_file($copia) ? $copia : QR_RAIZ . '/qrcode/admin/contador.json';
        if (!is_file($origem)) {
            return;
        }

        $antigo = json_decode((string) file_get_contents($origem), true);
        if (!is_array($antigo) || !$antigo) {
            error_log('qrcodes: ' . $origem . ' vazio ou ilegível; importação não feita');
            return;
        }

        $agora = date('Y-m-d H:i:s');
        $novos = [];
        foreach ($antigo as $alias => $item) {
            $url = is_array($item) ? (string) ($item['url'] ?? '') : '';
            if (!is_string($alias) || $alias === '' || !Destinos::urlValida($url)) {
                continue;
            }
            $novos[$alias] = [
                'url'          => $url,
                'status'       => 'ativo',
                'obs'          => 'Importado do sistema antigo em ' . date('d/m/Y') . '.',
                'formato'      => 'antigo',
                'anteriores'   => max(0, (int) ($item['count'] ?? 0)),
                'criado_em'    => $agora,
                'criado_por'   => 'importação',
                'alterado_em'  => $agora,
                'alterado_por' => 'importação',
            ];
        }
        if (!$novos) {
            return;
        }

        Armazem::atualizar('destinos', fn (array $atuais): ?array => $atuais ? null : $novos);
        if (isset($novos['default']) && !Ajustes::obter('padrao')) {
            Armazem::atualizar('ajustes', fn (array $a): array => $a + ['padrao' => $novos['default']['url']]);
        }
        Historico::registrar('importação', 'importar', 'contador.json', ['qrcodes' => array_keys($novos)]);
        if ($origem === $copia) {
            @rename($origem, QR_IMPORTAR . '/contador.importado-' . date('Ymd-His') . '.json');
        }
    }
}
