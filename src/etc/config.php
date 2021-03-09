<?php

/**
 * mage2devbox Configuration file
 */

return [
    'supported_versions' => [
        '2.3.5' => [
            'recipe_class'    => 'Mage23',
            'long_version'    => '2.3.5',
            'short_version'   => '235',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.6' => [
            'recipe_class'    => 'Mage23',
            'long_version'    => '2.3.6',
            'short_version'   => '236',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.4.1' => [
            'recipe_class'    => 'Mage24',
            'long_version'    => '2.4.1',
            'short_version'   => '241',
            'php_img_version' => '7.4',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.2' => [
            'recipe_class'    => 'Mage24',
            'long_version'    => '2.4.2',
            'short_version'   => '242',
            'php_img_version' => '7.4-composer2',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
    ],
    'default_env'        => [
        'M2D_DC_PROJECT_NAME' => 'mage2devbox',
        'M2D_WEB_PORT'        => 8080,
        'M2D_DB_PORT'         => 33306,
        'M2D_ES_PORT'         => 9200,
        'M2D_ES_CONTROL_PORT' => 9300,
        'M2D_TIMEZONE'        => 'Europe/Berlin',
        'M2D_MAGE_WEB_DOMAIN' => 'm2.docker',
        'M2D_MAGE_ADMIN_USER' => 'admin',
        'M2D_MAGE_ADMIN_PASS' => 'Admin123!',
        'M2D_MAGE_LANG'       => 'en_US',
        'M2D_MAGE_CURRENCY'   => 'EUR',
    ],
];