<?php

namespace Tests\Feature;

use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjetosTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'senha-bem-longa-123']));
    }

    public function test_estado_vazio_honesto(): void
    {
        $this->entrar();

        $this->get('/projetos')
            ->assertOk()
            ->assertSee('Nenhum projeto ainda')
            ->assertSee('Nada aqui é exemplo');
    }

    public function test_lista_projetos_existentes_escapando_o_conteudo(): void
    {
        Projeto::create(['titulo' => '<script>alert(1)</script>', 'descricao' => 'Descrição "teste"']);
        $this->entrar();

        $this->get('/projetos')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('Nenhum projeto ainda');
    }
}
