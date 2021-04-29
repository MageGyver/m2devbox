<?php declare(strict_types=1);
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace MageGyver\M2devbox\Util;

use MageGyver\M2devbox\Util\Env;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MageGyver\M2devbox\Util\Env
 */
class EnvTest extends TestCase
{

    public function testExtrapolateEnv()
    {
        $_ENV = [
            'M2D_DC_PROJECT_NAME' => 'm2d test',
            'M2D_TIMEZONE' => 'Europe/Berlin',
            'M2D_MAGE_LANG' => 'de_DE',
        ];

        $input = 'project = $(M2D_DC_PROJECT_NAME), tz = $(M2D_TIMEZONE), lang = $(M2D_MAGE_LANG)';
        $expected = 'project = m2d test, tz = Europe/Berlin, lang = de_DE';

        self::assertEquals(
            $expected,
            Env::extrapolateEnv($input)
        );
    }

    public function testDontExtrapolateDisallowedEnv()
    {
        $input = 'server = $(SERVER_NAME), tz = $(USERNAME)';

        self::assertEquals(
            $input,
            Env::extrapolateEnv($input)
        );
    }
}
