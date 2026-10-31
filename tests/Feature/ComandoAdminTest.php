<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComandoAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_nao_aceita_senha_por_argumento_ou_opcao(): void
    {
        $definicao = Artisan::all()['painel:admin']->getDefinition();

        foreach (['senha', 'password', 'pass', 'secret'] as $nome) {
            $this->assertFalse($definicao->hasOption($nome), "Opção proibida: --{$nome}");
            $this->assertFalse($definicao->hasArgument($nome), "Argumento proibido: {$nome}");
        }
    }

    public function test_cria_administrador_com_senha_oculta_e_confirmada(): void
    {
        $this->artisan('painel:admin', ['--email' => 'Admin@Example.com', '--nome' => 'Admin'])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'uma-senha-bem-longa-123')
            ->expectsQuestion('Confirme a senha', 'uma-senha-bem-longa-123')
            ->assertExitCode(0);

        $u = User::firstWhere('email', 'admin@example.com');

        $this->assertNotNull($u);
        $this->assertTrue(Hash::check('uma-senha-bem-longa-123', $u->password));
    }

    public function test_recusa_senhas_diferentes_curtas_ou_segundo_administrador(): void
    {
        $this->artisan('painel:admin', ['--email' => 'a@example.com', '--nome' => 'A'])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'uma-senha-bem-longa-123')
            ->expectsQuestion('Confirme a senha', 'outra-coisa-diferente-1')
            ->assertExitCode(1);

        $this->artisan('painel:admin', ['--email' => 'a@example.com', '--nome' => 'A'])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'curta')
            ->expectsQuestion('Confirme a senha', 'curta')
            ->assertExitCode(1);

        $this->assertSame(0, User::count());

        User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'uma-senha-bem-longa-123']);

        $this->artisan('painel:admin', ['--email' => 'b@example.com', '--nome' => 'B'])->assertExitCode(1);
        $this->assertSame(1, User::count());
    }

    public function test_redefine_a_senha_do_administrador_existente(): void
    {
        User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'uma-senha-bem-longa-123']);

        $this->artisan('painel:admin', ['--redefinir' => true])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'nova-senha-bem-longa-456')
            ->expectsQuestion('Confirme a senha', 'nova-senha-bem-longa-456')
            ->assertExitCode(0);

        $u = User::first();
        $this->assertTrue(Hash::check('nova-senha-bem-longa-456', $u->password));
        $this->assertFalse(Hash::check('uma-senha-bem-longa-123', $u->password));
        $this->assertSame(1, User::count());
    }

    public function test_redefinir_sem_administrador_falha(): void
    {
        $this->artisan('painel:admin', ['--redefinir' => true])->assertExitCode(1);
    }
}
