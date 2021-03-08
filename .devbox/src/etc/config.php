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
            'php_img_version' => '7.3',
            'compose_files' => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.6' => [
            'recipe_class' => 'Mage23',
            'long_version' => '2.3.6',
            'short_version' => '236',
            'php_img_version' => '7.3',
            'compose_files' => ['docker-compose.mage-2.3.yml'],
        ],
        '2.4.1' => [
            'recipe_class' => 'Mage24',
            'long_version' => '2.4.1',
            'short_version' => '241',
            'php_img_version' => '7.4',
            'compose_files' => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.2' => [
            'recipe_class' => 'Mage24',
            'long_version' => '2.4.2',
            'short_version' => '242',
            'php_img_version' => '7.4-composer2',
            'compose_files' => ['docker-compose.mage-2.4.yml'],
        ],
    ],
];
