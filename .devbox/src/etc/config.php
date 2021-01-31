<?php

/**
 * mage2devbox Configuration file
 */

return [
    'supported_versions' => [
        '2.3.5' => [
            'recipe_class' => 'Mage23',
            'long_version' => '2.3.5',
            'short_version' => '235',
            'compose_files' => ['docker-compose.mage-2.3.5.yml'],
        ],
        '2.3.6' => [
            'recipe_class' => 'Mage23',
            'long_version' => '2.3.6',
            'short_version' => '236',
            'compose_files' => ['docker-compose.mage-2.3.6.yml'],
        ],
        '2.4.1' => [
            'recipe_class' => 'Mage24',
            'long_version' => '2.4.1',
            'short_version' => '241',
            'compose_files' => ['docker-compose.mage-2.4.1.yml'],
        ],
    ],
];
