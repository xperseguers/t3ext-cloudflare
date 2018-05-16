<?php

return [
    'ctrl' => [
        'label' => 'cache_command',
        'hideTable' => 1
    ],
    'columns' => [
        'page_uid' => [
            'config' => [
                'type' => 'input',
                'eval' => 'int'
            ]
        ],
        'cache_command' => [
            'config' => [
                'type' => 'input',
                'eval' => 'int'
            ]
        ],
        'cache_tag' => [
            'config' => [
                'type' => 'input',
                'eval' => 'trim'
            ]
        ]
    ]
];
