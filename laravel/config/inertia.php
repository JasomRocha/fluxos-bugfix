<?php

// Mantem o padrao do pacote, so aponta para a pasta Pages (com P maiusculo) deste projeto.
return [
    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['vue'],
    ],
    'testing' => [
        'ensure_pages_exist' => true,
    ],
];
