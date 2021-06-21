<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

/**
 * m2devbox Configuration file
 */

use MageGyver\M2devbox\Recipe\Mage23;
use MageGyver\M2devbox\Recipe\Mage24;

// @codeCoverageIgnoreStart
return [
    'supported_versions' => [
        '2.3.4' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.4',
            'short_version'   => '234',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.4-p2' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.4-p2',
            'short_version'   => '234p2',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.5' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.5',
            'short_version'   => '235',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.5-p1' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.5-p1',
            'short_version'   => '235p1',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.5-p2' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.5-p2',
            'short_version'   => '235p2',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.6' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.6',
            'short_version'   => '236',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.6-p1' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.6-p1',
            'short_version'   => '236p1',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.3.7' => [
            'recipe_class'    => Mage23::class,
            'long_version'    => '2.3.7',
            'short_version'   => '237',
            'php_version'     => '7.3',
            'php_img_version' => '7.3',
            'compose_files'   => ['docker-compose.mage-2.3.yml'],
        ],
        '2.4.0' => [
            'recipe_class'    => Mage24::class,
            'long_version'    => '2.4.0',
            'short_version'   => '240',
            'php_version'     => '7.4',
            'php_img_version' => '7.4',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.0-p1' => [
            'recipe_class'    => Mage24::class,
            'long_version'    => '2.4.0-p1',
            'short_version'   => '240p1',
            'php_version'     => '7.4',
            'php_img_version' => '7.4',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.1' => [
            'recipe_class'    => Mage24::class,
            'long_version'    => '2.4.1',
            'short_version'   => '241',
            'php_version'     => '7.4',
            'php_img_version' => '7.4',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.1-p1' => [
            'recipe_class'    => Mage24::class,
            'long_version'    => '2.4.1-p1',
            'short_version'   => '241p1',
            'php_version'     => '7.4',
            'php_img_version' => '7.4',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.2' => [
            'recipe_class'    => Mage24::class,
            'long_version'    => '2.4.2',
            'short_version'   => '242',
            'php_version'     => '7.4',
            'php_img_version' => '7.4-composer2',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
        '2.4.2-p1' => [
            'recipe_class'    => Mage24::class,
            'long_version'    => '2.4.2-p1',
            'short_version'   => '242p1',
            'php_version'     => '7.4',
            'php_img_version' => '7.4-composer2',
            'compose_files'   => ['docker-compose.mage-2.4.yml'],
        ],
    ],
    'default_env'        => [
        'M2D_DC_PROJECT_NAME' => 'm2devbox',
        'M2D_APP_CODE'        => './app_code/',
        'M2D_WEB_PORT'        => 8080,
        'M2D_DB_PORT'         => 33306,
        'M2D_ES_PORT'         => 9200,
        'M2D_ES_CONTROL_PORT' => 9300,
        'M2D_REDIS_PORT'      => 6379,
        'M2D_TIMEZONE'        => 'Europe/Berlin',
        'M2D_MAGE_WEB_DOMAIN' => 'm2.docker',
        'M2D_MAGE_ADMIN_USER' => 'admin',
        'M2D_MAGE_ADMIN_PASS' => 'Admin123!',
        'M2D_MAGE_LANG'       => 'en_US',
        'M2D_MAGE_CURRENCY'   => 'EUR',
    ],
];
// @codeCoverageIgnoreEnd
