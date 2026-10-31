<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class PainelBackup extends Command
{
    protected $signature = 'painel:backup {--destino=/backups : Pasta de destino (precisa existir e ser gravável)}';

    protected $description = 'Gera uma cópia consistente do SQLite (VACUUM INTO), segura mesmo com o painel em uso.';

    public function handle(): int
    {
        $destino = rtrim((string) $this->option('destino'), '/');

        if ($destino === '' || ! is_dir($destino) || ! is_writable($destino)) {
            $this->error('Pasta de destino inexistente ou sem permissão de escrita: '.$destino);

            return self::FAILURE;
        }

        $arquivo = $destino.'/banco-'.now()->format('Ymd-His').'.sqlite';

        if (file_exists($arquivo)) {
            $this->error('Já existe um arquivo com esse nome; tente novamente em instantes.');

            return self::FAILURE;
        }

        DB::statement('VACUUM INTO '.DB::connection()->getPdo()->quote($arquivo));

        $copia = new PDO('sqlite:'.$arquivo);
        $resultado = $copia->query('PRAGMA integrity_check')->fetchColumn();
        $copia = null;

        if ($resultado !== 'ok') {
            @unlink($arquivo);
            $this->error('A cópia falhou na verificação de integridade e foi descartada.');

            return self::FAILURE;
        }

        chmod($arquivo, 0600);
        $this->info('Backup do banco criado e verificado: '.$arquivo);

        return self::SUCCESS;
    }
}
