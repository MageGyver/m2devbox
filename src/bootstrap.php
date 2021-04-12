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

use MageGyver\M2devbox\Service\Config;
use Dotenv\Dotenv;

define('DB_ROOT', dirname(__DIR__));
const DB_SRC      = DB_ROOT . '/src';
const M2D_VERSION = '@git_tag@';

require DB_ROOT.'/vendor/autoload.php';

$cwd = getcwd();

// load default ENV vars form config
$defaultEnv = Config::get('default_env');
foreach ($defaultEnv as $key => $value) {
    if (!empty($value) && !array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
        putenv($key.'='.$value);
    }
}

// load custom ENV vars from $CWD/.env
if (file_exists($cwd.'/.env')) {
    $dotenv = Dotenv::createMutable($cwd);
    $dotenv->load();
}
