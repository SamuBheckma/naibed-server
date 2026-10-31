<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA = 'uma-senha-bem-longa-123';

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => self::SENHA,
        ]);
    }

    public function test_visitante_sem_login_nao_acessa_paginas_privadas(): void
    {
        $this->get('/')->assertRedirect('/entrar');
        $this->get('/projetos')->assertRedirect('/entrar');
    }

    public function test_rotas_privadas_continuam_protegidas_em_acesso_direto(): void
    {
        // Logout por POST sem sessão também não passa.
        $this->post('/sair')->assertRedirect('/entrar');
        $this->get('/projetos')->assertRedirect('/entrar');
        $this->assertGuest();
    }

    public function test_tela_de_login_e_publica(): void
    {
        $this->get('/entrar')->assertOk()->assertSee('Entrar');
    }

    public function test_login_valido_funciona_e_regenera_a_sessao(): void
    {
        $this->admin();

        $resposta = $this->post('/entrar', [
            'email' => 'admin@example.com',
            'password' => self::SENHA,
        ]);

        $resposta->assertRedirect('/');
        $this->assertAuthenticated();
        $this->get('/')->assertOk();
    }

    public function test_login_invalido_e_rejeitado_com_mensagem_generica(): void
    {
        $this->admin();

        $comSenhaErrada = $this->post('/entrar', ['email' => 'admin@example.com', 'password' => 'errada']);
        $comEmailInexistente = $this->post('/entrar', ['email' => 'ninguem@example.com', 'password' => 'errada']);

        $mensagem = 'Não foi possível entrar com esses dados.';

        $comSenhaErrada->assertRedirect('/entrar')->assertSessionHas('erro', $mensagem);
        $comEmailInexistente->assertRedirect('/entrar')->assertSessionHas('erro', $mensagem);
        $this->assertGuest();
    }

    public function test_tentativas_repetidas_sofrem_limitacao(): void
    {
        $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/entrar', ['email' => 'admin@example.com', 'password' => 'errada'.$i])
                ->assertRedirect('/entrar');
        }

        // Mesmo com a senha CERTA, a sexta tentativa é barrada.
        $this->post('/entrar', ['email' => 'admin@example.com', 'password' => self::SENHA])
            ->assertStatus(429)
            ->assertSee('Muitas tentativas');

        $this->assertGuest();
    }

    public function test_logout_invalida_a_sessao(): void
    {
        $this->actingAs($this->admin());
        $this->get('/')->assertOk();

        $this->post('/sair')->assertRedirect('/entrar');

        $this->assertGuest();
        $this->get('/')->assertRedirect('/entrar');
    }

    public function test_logout_nao_funciona_por_get(): void
    {
        $this->actingAs($this->admin());

        $this->get('/sair')->assertStatus(405);
        $this->assertAuthenticated();
    }

    public function test_nao_existe_cadastro_publico_nem_recuperacao_de_senha(): void
    {
        foreach (['/register', '/cadastro', '/cadastrar', '/forgot-password', '/esqueci-a-senha', '/password/reset'] as $caminho) {
            $this->get($caminho)->assertNotFound();
        }

        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'x'])->assertStatus(404);
        $this->assertSame(0, User::count());
    }

    public function test_usuario_logado_nao_ve_a_tela_de_login(): void
    {
        $this->actingAs($this->admin());

        $this->get('/entrar')->assertRedirect('/');
    }
}
