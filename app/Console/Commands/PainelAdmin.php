<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PainelAdmin extends Command
{
    /**
     * A senha NUNCA é aceita como argumento ou opção: ela é lida de forma
     * oculta, com confirmação, para não ficar no histórico do shell nem na
     * lista de processos.
     */
    protected $signature = 'painel:admin
        {--email= : E-mail de acesso (pergunta se omitido)}
        {--nome= : Nome exibido (pergunta se omitido)}
        {--redefinir : Redefine a senha do administrador existente}';

    protected $description = 'Cria o administrador do painel ou redefine a senha dele (senha lida de forma oculta).';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Execute em um terminal interativo. A senha nunca é aceita como argumento.');

            return self::FAILURE;
        }

        $existente = User::query()->orderBy('id')->first();

        if ($this->option('redefinir')) {
            if (! $existente) {
                $this->error('Ainda não existe administrador. Rode o comando sem --redefinir.');

                return self::FAILURE;
            }

            $informado = $this->option('email');

            if (is_string($informado) && $informado !== '' && strtolower(trim($informado)) !== strtolower($existente->email)) {
                $this->error('O e-mail informado não corresponde ao administrador existente.');

                return self::FAILURE;
            }

            $usuario = $existente;
        } else {
            if ($existente) {
                $this->error('Já existe um administrador ('.$existente->email.'). Use --redefinir para trocar a senha.');

                return self::FAILURE;
            }

            $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('E-mail de acesso'))));
            $nome = trim((string) ($this->option('nome') ?: $this->ask('Nome exibido', 'Administrador')));

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
                $this->error('E-mail inválido.');

                return self::FAILURE;
            }

            if ($nome === '' || mb_strlen($nome) > 100) {
                $this->error('O nome deve ter de 1 a 100 caracteres.');

                return self::FAILURE;
            }

            $usuario = new User(['name' => $nome, 'email' => $email]);
        }

        $senha = $this->secret('Senha (mínimo 12 caracteres)', false);
        $confirmacao = $this->secret('Confirme a senha', false);

        if (! is_string($senha) || $senha === '' || ! hash_equals($senha, (string) $confirmacao)) {
            $this->error('As senhas não conferem.');

            return self::FAILURE;
        }

        if (mb_strlen($senha) < 12 || strlen($senha) > 256) {
            $this->error('A senha deve ter de 12 a 256 caracteres.');

            return self::FAILURE;
        }

        if (strtolower($senha) === strtolower((string) $usuario->email)) {
            $this->error('A senha não pode ser igual ao e-mail.');

            return self::FAILURE;
        }

        $usuario->password = $senha; // cast "hashed": gravada com hash.
        $usuario->save();

        if ($this->option('redefinir')) {
            $this->encerrarSessoesAbertas();
            $this->info('Senha redefinida. As sessões abertas foram encerradas.');
        } else {
            $this->info('Administrador criado: '.$usuario->email);
        }

        return self::SUCCESS;
    }

    private function encerrarSessoesAbertas(): void
    {
        if (config('session.driver') !== 'file') {
            return;
        }

        foreach (glob(rtrim((string) config('session.files'), '/').'/*') ?: [] as $arquivo) {
            if (is_file($arquivo)) {
                @unlink($arquivo);
            }
        }
    }
}
