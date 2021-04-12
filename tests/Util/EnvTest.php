<?php declare(strict_types=1);
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace Util;

use MageGyver\M2devbox\Util\Env;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Devbox\Util\Env
 */
class EnvTest extends TestCase
{

    public function testExtrapolateEnv()
    {
        $_ENV = [
            'DB_NAME' => 'database',
            'DB_USER' => 'username',
            'DB_PASS' => 'password',
        ];

        $input = 'name = $(DB_NAME), user = $(DB_USER), pass = $(DB_PASS)';
        $expected = 'name = database, user = username, pass = password';

        self::assertEquals(
            $expected,
            Env::extrapolateEnv($input)
        );
    }
}
