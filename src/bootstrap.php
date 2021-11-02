<?php declare(strict_types=1);
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace MageGyver\M2devbox;

define('DB_ROOT', dirname(__DIR__));
const DB_SRC      = DB_ROOT . '/src';
const M2D_VERSION = '@git_tag@';

// use correct autoload file, depending on install method (composer package or phar)
$autoloadCandidates = [
    DB_ROOT . '/vendor/autoload.php',
    DB_ROOT . '/../../autoload.php',
];
foreach ($autoloadCandidates as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}

// load env vars
Devbox::loadEnv();
