<?php

namespace App\Support;

/**
 * Lê os atalhos configurados no servidor e valida cada endereço.
 *
 * Um link só é entregue ao navegador se for http(s), sem credenciais embutidas
 * e sem apontar para a própria máquina (localhost/127.x/::1), pois esses
 * endereços apontariam para o dispositivo do visitante, não para o servidor.
 */
final class Atalhos
{
    public const OK = 'ok';

    public const NAO_CONFIGURADO = 'nao_configurado';

    public const INVALIDO = 'invalido';

    /**
     * @return list<array{titulo:string,descricao:string,icone:string,url:?string,estado:string}>
     */
    public static function todos(): array
    {
        $itens = config('painel.atalhos', []);

        foreach (self::extras() as $extra) {
            $itens[] = $extra;
        }

        $saida = [];

        foreach ($itens as $item) {
            $avaliado = self::avaliar(is_string($item['url'] ?? null) ? $item['url'] : null);

            $saida[] = [
                'titulo' => (string) ($item['titulo'] ?? 'Atalho'),
                'descricao' => (string) ($item['descricao'] ?? ''),
                'icone' => (string) ($item['icone'] ?? 'link'),
                'url' => $avaliado['url'],
                'estado' => $avaliado['estado'],
            ];
        }

        return $saida;
    }

    /**
     * @return array{estado:string,url:?string}
     */
    public static function avaliar(?string $bruto): array
    {
        $url = trim((string) $bruto);

        if ($url === '') {
            return ['estado' => self::NAO_CONFIGURADO, 'url' => null];
        }

        if (strlen($url) > 2048 || preg_match('/[\x00-\x20\x7F]/', $url) === 1) {
            return ['estado' => self::INVALIDO, 'url' => null];
        }

        $partes = parse_url($url);

        if ($partes === false) {
            return ['estado' => self::INVALIDO, 'url' => null];
        }

        $esquema = strtolower((string) ($partes['scheme'] ?? ''));
        $host = strtolower((string) ($partes['host'] ?? ''));

        if (! in_array($esquema, ['http', 'https'], true) || $host === '') {
            return ['estado' => self::INVALIDO, 'url' => null];
        }

        if (isset($partes['user']) || isset($partes['pass'])) {
            return ['estado' => self::INVALIDO, 'url' => null];
        }

        if (self::hostLocal($host) || ! self::hostPlausivel($host)) {
            return ['estado' => self::INVALIDO, 'url' => null];
        }

        return ['estado' => self::OK, 'url' => $url];
    }

    private static function hostPlausivel(string $host): bool
    {
        $semColchetes = trim($host, '[]');

        if (filter_var($semColchetes, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        // Navegadores tratam "127.1", "2130706433" ou "0x7f.1" como IPv4. Se o último
        // rótulo é numérico e não é um IP completo válido, rejeita (evita burlar o bloqueio de loopback).
        $rotulos = explode('.', rtrim($host, '.'));
        $ultimo = end($rotulos);

        if (ctype_digit($ultimo) || preg_match('/^0x[0-9a-f]*$/i', $ultimo) === 1) {
            return false;
        }

        return preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.?$/', $host) === 1;
    }

    private static function hostLocal(string $host): bool
    {
        $h = rtrim(trim($host, '[]'), '.');

        if ($h === 'localhost' || str_ends_with($h, '.localhost')) {
            return true;
        }

        if (filter_var($h, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return str_starts_with($h, '127.') || $h === '0.0.0.0';
        }

        if (filter_var($h, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $binario = inet_pton($h);

            if ($binario === false) {
                return true;
            }

            $loopback = str_repeat("\x00", 15)."\x01";
            $nao_especificado = str_repeat("\x00", 16);
            $mapeado = str_repeat("\x00", 10)."\xff\xff";

            if ($binario === $loopback || $binario === $nao_especificado) {
                return true;
            }

            // IPv4 mapeado em IPv6 (::ffff:127.0.0.1).
            return str_starts_with($binario, $mapeado) && ord($binario[12]) === 127;
        }

        return false;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private static function extras(): array
    {
        $json = config('painel.atalhos_extras_json');

        if (! is_string($json) || trim($json) === '') {
            return [];
        }

        $dados = json_decode($json, true);

        if (! is_array($dados) || ! array_is_list($dados)) {
            return [];
        }

        $saida = [];

        foreach (array_slice($dados, 0, 24) as $d) {
            if (! is_array($d) || ! is_string($d['titulo'] ?? null) || trim($d['titulo']) === '') {
                continue;
            }

            $saida[] = [
                'titulo' => mb_substr(trim($d['titulo']), 0, 60),
                'descricao' => is_string($d['descricao'] ?? null) ? mb_substr(trim($d['descricao']), 0, 140) : '',
                'icone' => 'link',
                'url' => is_string($d['url'] ?? null) ? $d['url'] : null,
            ];
        }

        return $saida;
    }
}
