<?php

namespace Tests\Feature;

use App\Models\Nota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotasTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $email = 'notas@example.com'): User
    {
        return User::create(['name' => 'Admin', 'email' => $email, 'password' => 'senha-bem-longa-123']);
    }

    private function nota(User $user, string $titulo, string $criada, string $editada): Nota
    {
        $nota = new Nota(['titulo' => $titulo, 'conteudo' => "Linha um\nLinha dois"]);
        $nota->user_id = $user->id;
        $nota->created_at = $criada;
        $nota->updated_at = $editada;
        $nota->save();
        return $nota;
    }

    public function test_todas_as_rotas_exigem_login(): void
    {
        foreach (['/notas', '/notas/criar', '/notas/1/editar', '/notas/1/excluir'] as $url) {
            $this->get($url)->assertRedirect('/entrar');
        }
        $this->post('/notas', [])->assertRedirect('/entrar');
        $this->put('/notas/1', [])->assertRedirect('/entrar');
        $this->delete('/notas/1')->assertRedirect('/entrar');
    }

    public function test_cria_edita_e_exclui_nota_propria(): void
    {
        $user = $this->usuario();
        $this->actingAs($user)->post('/notas', ['titulo' => 'Minha nota', 'conteudo' => "Texto\ncom linhas"])
            ->assertRedirect(route('notas.index'));
        $nota = Nota::firstOrFail();
        $this->assertSame($user->id, (int) $nota->user_id);
        $this->get('/notas/'.$nota->id.'/editar')->assertOk()->assertSee('Minha nota');
        $this->put('/notas/'.$nota->id, ['titulo' => 'Nota alterada', 'conteudo' => 'Outro texto'])
            ->assertRedirect(route('notas.index'));
        $this->assertDatabaseHas('notas', ['id' => $nota->id, 'titulo' => 'Nota alterada']);
        $this->get('/notas/'.$nota->id.'/excluir')->assertOk()->assertSee('Confirmar exclusão');
        $this->assertDatabaseCount('notas', 1); // GET de confirmação não exclui.
        $this->delete('/notas/'.$nota->id)->assertRedirect(route('notas.index'));
        $this->assertDatabaseCount('notas', 0);
    }

    public function test_nao_expoe_nem_altera_notas_de_outro_usuario(): void
    {
        $dono = $this->usuario();
        $nota = $this->nota($dono, 'SEGREDO DO OUTRO', '2026-01-01', '2026-01-01');
        $this->actingAs($this->usuario('outro@example.com'));
        $this->get('/notas')->assertOk()->assertDontSee('SEGREDO DO OUTRO');
        $this->get('/notas/'.$nota->id.'/editar')->assertNotFound();
        $this->get('/notas/'.$nota->id.'/excluir')->assertNotFound();
        $this->put('/notas/'.$nota->id, ['titulo' => 'Invadida', 'conteudo' => 'Texto'])->assertNotFound();
        $this->delete('/notas/'.$nota->id)->assertNotFound();
        $this->assertDatabaseHas('notas', ['id' => $nota->id, 'titulo' => 'SEGREDO DO OUTRO']);
    }

    public function test_ordena_pelos_dois_campos_nas_duas_direcoes(): void
    {
        $user = $this->usuario();
        $this->nota($user, 'ALFA TESTE', '2026-01-01', '2026-03-01');
        $this->nota($user, 'BETA TESTE', '2026-02-01', '2026-02-01');
        $this->actingAs($user);
        foreach ([
            ['created_at', 'asc', ['ALFA TESTE', 'BETA TESTE']],
            ['created_at', 'desc', ['BETA TESTE', 'ALFA TESTE']],
            ['updated_at', 'asc', ['BETA TESTE', 'ALFA TESTE']],
            ['updated_at', 'desc', ['ALFA TESTE', 'BETA TESTE']],
        ] as [$campo, $direcao, $titulos]) {
            $this->get('/notas?ordenar='.$campo.'&direcao='.$direcao)->assertOk()->assertSeeInOrder($titulos);
        }
        $this->get('/notas?ordenar=invalid&direcao=invalid')->assertOk()
            ->assertSeeInOrder(['ALFA TESTE', 'BETA TESTE']);
    }

    public function test_validacao_e_propriedade_na_criacao(): void
    {
        $user = $this->usuario();
        $this->actingAs($user);
        $this->post('/notas', ['titulo' => '', 'conteudo' => ''])->assertSessionHasErrors(['titulo', 'conteudo']);
        $this->post('/notas', ['titulo' => str_repeat('a', 121), 'conteudo' => str_repeat('b', 20001)])
            ->assertSessionHasErrors(['titulo', 'conteudo']);
        $this->assertDatabaseCount('notas', 0);
        $this->post('/notas', ['titulo' => 'Válida', 'conteudo' => 'Texto', 'user_id' => 99999]);
        $this->assertSame($user->id, (int) Nota::firstOrFail()->user_id);
    }

    public function test_texto_html_e_exibido_sem_executar(): void
    {
        $user = $this->usuario();
        $this->actingAs($user)->post('/notas', ['titulo' => '<script>alert(1)</script>', 'conteudo' => '<img src=x onerror=alert(1)>']);
        $this->get('/notas')->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x', false);
    }

    public function test_editar_altera_data_sem_mudar_criacao_e_salvar_igual_nao_altera(): void
    {
        $user = $this->usuario();
        $nota = $this->nota($user, 'Antes', '2026-01-01 10:00:00', '2026-01-01 10:00:00');
        $this->actingAs($user);
        $this->travelTo(now()->setDate(2026, 5, 1)->setTime(12, 0));
        $this->put('/notas/'.$nota->id, ['titulo' => 'Depois', 'conteudo' => 'Novo texto']);
        $nota->refresh();
        $this->assertSame('2026-01-01 10:00:00', $nota->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-01 12:00:00', $nota->updated_at->format('Y-m-d H:i:s'));
        $this->travel(1)->hours();
        $this->put('/notas/'.$nota->id, ['titulo' => 'Depois', 'conteudo' => 'Novo texto']);
        $this->assertSame('2026-05-01 12:00:00', $nota->fresh()->updated_at->format('Y-m-d H:i:s'));
        $this->travelBack();
    }

    public function test_paginacao_preserva_ordem_e_desempata_por_id(): void
    {
        $user = $this->usuario();
        for ($i = 0; $i < 25; $i++) {
            $this->nota($user, sprintf('NOTA %02d', $i), '2026-01-01', '2026-01-01');
        }
        $this->actingAs($user)->get('/notas?ordenar=created_at&direcao=asc')
            ->assertOk()->assertSeeInOrder(['NOTA 00', 'NOTA 23'])->assertDontSee('NOTA 24')
            ->assertSee('ordenar=created_at', false)->assertSee('direcao=asc', false);
        $this->get('/notas?ordenar=created_at&direcao=asc&page=2')
            ->assertOk()->assertSee('NOTA 24')->assertDontSee('NOTA 00');
    }
}
