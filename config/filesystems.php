<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // Sem rota pública para arquivos do disco local.
            'serve' => false,
            'throw' => true,
            'report' => false,
        ],
    ],

    // Sem links simbólicos: nada de storage/ é publicado.
    'links' => [],
];
