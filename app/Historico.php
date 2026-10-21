<?php
/**
 * Histórico de alterações (dados/historico.php): quem mudou o quê e quando.
 * Uma linha JSON por evento, só acrescentada. Ex.: troca de destino, pausa, novo usuário.
 */

final class Historico
{
    public static function registrar(string $usuario, string $acao, string $alvo, array $detalhes = []): void
    {
        $evento = [
            'quando'   => date('Y-m-d H:i:s'),
            'usuario'  => $usuario,
            'acao'     => $acao,
            'alvo'     => $alvo,
            'detalhes' => $detalhes,
        ];
        Armazem::acrescentar('historico', json_encode($evento, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** Eventos mais recentes primeiro; $alvo filtra por QR code (ou ajuste). */
    public static function recentes(?string $alvo = null, int $limite = 100): array
    {
        $eventos = [];
        foreach (array_reverse(Armazem::linhas('historico')) as $linha) {
            $evento = json_decode($linha, true);
            if (is_array($evento) && ($alvo === null || ($evento['alvo'] ?? null) === $alvo)) {
                $eventos[] = $evento;
                if (count($eventos) >= $limite) {
                    break;
                }
            }
        }

        return $eventos;
    }
}
