<?php
/**
 * Ajustes da instalação, editáveis no painel por administradores (dados/ajustes.php):
 *   nome    nome que aparece no painel (ex.: "QR codes Cliente")
 *   padrao  destino de quem chega com um nome inexistente ou num QR code pausado
 *   base    endereço público da instalação, usado dentro dos QR codes (ex.: https://qr.cliente.com.br)
 *   fuso    fuso horário dos registros (padrão: America/Sao_Paulo)
 */

final class Ajustes
{
    private static ?array $cache = null;

    public static function todos(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = Armazem::ler('ajustes') ?? [];
            } catch (RuntimeException $erro) {
                error_log('qrcodes: ' . $erro->getMessage());
                self::$cache = [];
            }
        }

        return self::$cache;
    }

    public static function obter(string $chave, ?string $padrao = null): ?string
    {
        $valor = self::todos()[$chave] ?? null;

        return is_string($valor) && $valor !== '' ? $valor : $padrao;
    }

    /** Salva ajustes e registra no histórico o que mudou. */
    public static function salvar(array $novos, string $usuario): void
    {
        $antes = self::todos();
        self::$cache = Armazem::atualizar('ajustes', fn (array $atuais): array => array_merge($atuais, $novos));
        foreach ($novos as $chave => $valor) {
            if (($antes[$chave] ?? null) !== $valor) {
                Historico::registrar($usuario, 'ajuste', $chave, ['antes' => $antes[$chave] ?? null, 'depois' => $valor]);
            }
        }
    }

    /** Segredo da instalação (para as impressões cifradas do bloqueio de login). Criado na primeira vez. */
    public static function segredo(): string
    {
        $segredo = self::obter('segredo');
        if (!$segredo) {
            $novo = bin2hex(random_bytes(32));
            self::$cache = Armazem::atualizar('ajustes', fn (array $a): array => $a + ['segredo' => $novo]);
            $segredo = (string) self::$cache['segredo'];
        }

        return $segredo;
    }

    /** Endereço público da instalação: o salvo nos ajustes ou, na falta dele, o do acesso atual. */
    public static function base(): string
    {
        $salvo = self::obter('base');
        if ($salvo) {
            return rtrim($salvo, '/');
        }
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $pasta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        // os pontos de entrada ficam na raiz, em /qrcode e em /admin: a base é a pasta acima deles
        $pasta = preg_replace('~/(qrcode|admin)(/.*)?$~', '', $pasta) ?? $pasta;

        return ($https ? 'https' : 'http') . '://' . $host . $pasta;
    }
}
