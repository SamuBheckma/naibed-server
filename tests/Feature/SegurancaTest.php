<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SegurancaTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'senha-bem-longa-123']));
    }

    public function test_cabecalhos_de_seguranca_presentes(): void
    {
        $r = $this->get('/entrar');

        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('X-Frame-Options', 'DENY');
        $r->assertHeader('Referrer-Policy', 'no-referrer');

        $csp = $r->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'none'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        // Não forçar HTTPS: o acesso local e onion é HTTP.
        $this->assertNull($r->headers->get('Strict-Transport-Security'));
        $this->assertStringNotContainsString('upgrade-insecure-requests', $csp);
    }

    public function test_paginas_nao_chamam_recursos_externos(): void
    {
        config(['painel.atalhos.0.url' => null]);

        // Tela de login (visitante)
        $this->assertSemRecursoExterno($this->get('/entrar')->getContent());

        // Páginas privadas
        $this->entrar();
        $this->assertSemRecursoExterno($this->get('/')->getContent());
        $this->assertSemRecursoExterno($this->get('/projetos')->getContent());
    }

    private function assertSemRecursoExterno(string $html): void
    {
        // O Laravel também gera URLs absolutas para recursos do próprio painel.
        // Compare a origem para não confundir essas URLs com recursos externos.
        preg_match_all('/\s(?:src|href|action|poster|srcset)\s*=\s*["\']\s*((?:https?:)?\/\/[^"\']*)/i', $html, $matches);
        $origem = parse_url(url('/'));

        foreach ($matches[1] as $valor) {
            // srcset pode conter várias URLs, cada uma com seu descritor.
            foreach (explode(',', html_entity_decode($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8')) as $item) {
                $url = preg_split('/\s+/', trim($item))[0];
                if (!preg_match('~^(?:https?:)?//~i', $url)) {
                    continue;
                }

                $destino = parse_url(str_starts_with($url, '//') ? $origem['scheme'].':'.$url : $url);
                $mesmaOrigem = is_array($destino)
                    && strtolower($destino['scheme'] ?? '') === strtolower($origem['scheme'])
                    && strtolower($destino['host'] ?? '') === strtolower($origem['host'])
                    && ($destino['port'] ?? ($destino['scheme'] === 'https' ? 443 : 80))
                        === ($origem['port'] ?? ($origem['scheme'] === 'https' ? 443 : 80));

                $this->assertTrue($mesmaOrigem, 'Recurso externo encontrado: '.$url);
            }
        }
        $this->assertStringNotContainsString('@import', $html);
        $this->assertStringNotContainsString('url(http', $html);
        $this->assertStringNotContainsString('<script>', $html); // sem script inline
        $this->assertStringNotContainsString('style="', $html); // sem estilo inline
    }

    public function test_cookie_de_sessao_http_only_e_same_site(): void
    {
        $r = $this->get('/entrar');
        $nome = config('session.cookie');

        $cookie = collect($r->headers->getCookies())->first(fn ($c) => $c->getName() === $nome);

        $this->assertNotNull($cookie, 'Cookie de sessão não foi enviado.');
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
        // Padrão: sem Secure, para o login funcionar em HTTP (túnel SSH e onion).
        $this->assertFalse($cookie->isSecure());
    }

    public function test_cookie_ganha_secure_quando_configurado(): void
    {
        config(['session.secure' => true]);

        $r = $this->get('/entrar');
        $nome = config('session.cookie');
        $cookie = collect($r->headers->getCookies())->first(fn ($c) => $c->getName() === $nome);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
    }

    public function test_arquivos_internos_nao_sao_servidos_pela_aplicacao(): void
    {
        foreach (['/.env', '/database/data/database.sqlite', '/storage/logs/laravel.log', '/composer.json', '/artisan'] as $caminho) {
            $this->get($caminho)->assertNotFound();
        }
    }

    public function test_debug_desligado_nao_vaza_detalhes_em_erro(): void
    {
        $this->assertFalse(config('app.debug'));
        $this->get('/rota-inexistente')->assertNotFound()->assertDontSee('vendor/laravel');
    }

    public function test_senha_fica_com_hash_no_banco(): void
    {
        $u = User::create(['name' => 'Admin', 'email' => 'h@example.com', 'password' => 'senha-bem-longa-123']);

        $this->assertNotSame('senha-bem-longa-123', $u->getRawOriginal('password'));
        $this->assertTrue(password_verify('senha-bem-longa-123', $u->getRawOriginal('password')));
    }
}
