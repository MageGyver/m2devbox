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

// load env vars
Devbox::loadEnv();
