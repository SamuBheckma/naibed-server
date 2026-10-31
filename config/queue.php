<?php

return [
    // Nenhuma fila nesta versão: tudo é síncrono.
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
    ],

    'failed' => [
        'driver' => 'null',
    ],
];
