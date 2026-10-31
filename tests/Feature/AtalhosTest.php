<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Atalhos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtalhosTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'senha-bem-longa-123']));
    }

    public function test_sem_endereco_mostra_estado_nao_configurado_e_nenhum_link(): void
    {
        config(['painel.atalhos.0.url' => null]);
        $this->entrar();

        $this->get('/')
            ->assertOk()
            ->assertSee('Endereço não configurado')
            ->assertDontSee('target="_blank"', false);
    }

    public function test_endereco_valido_vira_link(): void
    {
        config(['painel.atalhos.0.url' => 'https://jellyfin.exemplo.onion/']);
        $this->entrar();

        $this->get('/')
            ->assertOk()
            ->assertSee('href="https://jellyfin.exemplo.onion/"', false)
            ->assertDontSee('Endereço não configurado');
    }

    public function test_enderecos_inseguros_ou_locais_sao_rejeitados(): void
    {
        $ruins = [
            'http://localhost:8096',
            'http://127.0.0.1:8096',
            'http://127.1.2.3/',
            'http://0.0.0.0/',
            'http://[::1]:8096/',
            'http://[::ffff:127.0.0.1]/',
            'http://app.localhost/',
            'http://localhost./',
            'http://127.1/',
            'http://2130706433/',
            'http://0x7f.1/',
            'javascript:alert(1)',
            'data:text/html;base64,AAAA',
            'ftp://exemplo.com/',
            '//exemplo.com/',
            'https://usuario:senha@exemplo.com/',
            "https://exemplo.com/ com espaço",
            'exemplo.com',
        ];

        foreach ($ruins as $url) {
            $this->assertNull(Atalhos::avaliar($url)['url'], "Deveria rejeitar: {$url}");
            $this->assertSame(Atalhos::INVALIDO, Atalhos::avaliar($url)['estado'], "Estado errado para: {$url}");
        }
    }

    public function test_enderecos_validos_sao_aceitos(): void
    {
        foreach (['http://exemplo.onion/', 'https://jellyfin.exemplo.com:8920/', 'http://192.168.0.10:8096/'] as $url) {
            $this->assertSame(Atalhos::OK, Atalhos::avaliar($url)['estado'], $url);
        }
    }

    public function test_endereco_invalido_nao_gera_link_na_pagina(): void
    {
        config(['painel.atalhos.0.url' => 'http://127.0.0.1:8096']);
        $this->entrar();

        $this->get('/')
            ->assertOk()
            ->assertSee('Endereço inválido')
            ->assertDontSee('127.0.0.1:8096')
            ->assertDontSee('target="_blank"', false);
    }

    public function test_atalhos_extras_em_json_sao_validados(): void
    {
        config(['painel.atalhos_extras_json' => json_encode([
            ['titulo' => 'Wiki', 'descricao' => 'Notas', 'url' => 'https://wiki.exemplo.onion/'],
            ['titulo' => 'Quebrado', 'url' => 'javascript:alert(1)'],
        ])]);

        $todos = Atalhos::todos();

        $this->assertCount(3, $todos); // Jellyfin + 2 extras
        $this->assertSame(Atalhos::OK, $todos[1]['estado']);
        $this->assertSame(Atalhos::INVALIDO, $todos[2]['estado']);
    }
}
