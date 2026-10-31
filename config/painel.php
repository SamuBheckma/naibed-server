<?php

return [
    // Nome exibido no painel. Altere PAINEL_NOME no .env.
    'nome' => env('PAINEL_NOME', 'Painel pessoal'),

    // Atalhos de serviços. Configurados no servidor (.env), sem editor no navegador.
    // O endereço precisa ser alcançável pelo DISPOSITIVO DO VISITANTE:
    // localhost/127.0.0.1 apontariam para o próprio aparelho dele e são rejeitados.
    'atalhos' => [
        [
            'chave' => 'jellyfin',
            'titulo' => 'Jellyfin',
            'descricao' => 'Biblioteca de mídia. Exige o login do próprio Jellyfin.',
            'icone' => 'play',
            'url' => env('PAINEL_JELLYFIN_URL'),
        ],
    ],

    // Atalhos extras opcionais, em JSON no .env:
    // PAINEL_ATALHOS='[{"titulo":"Wiki","descricao":"Notas","url":"https://exemplo.onion/"}]'
    'atalhos_extras_json' => env('PAINEL_ATALHOS'),
];
