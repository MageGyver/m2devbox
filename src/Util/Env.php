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

use function \preg_match, \array_walk, \strtr;

class Env
{
    const ENV_ALLOWED_LIST = [
        'M2D_DC_PROJECT_NAME',
        'M2D_WEB_PORT',
        'M2D_DB_PORT',
        'M2D_ES_PORT',
        'M2D_ES_CONTROL_PORT',
        'M2D_REDIS_PORT',
        'M2D_TIMEZONE',
        'M2D_MAGE_WEB_DOMAIN',
        'M2D_MAGE_ADMIN_USER',
        'M2D_MAGE_ADMIN_PASS',
        'M2D_MAGE_LANG',
        'M2D_MAGE_CURRENCY',
        'M2D_APP_CODE',
    ];

    /**
     * Extrapolate env variables in a string.
     *
     * @param string $string
     * @return string
     */
    public static function extrapolateEnv(string $string): string
    {
        if (preg_match('/\$\(.*\)/mU', $string) === 1) {
            $replacements = [];

            array_walk($_ENV, function ($v, $k) use (&$replacements) {
                if (in_array($k, self::ENV_ALLOWED_LIST)) {
                    $replacements['$(' . $k . ')'] = $v;
                }
            });

            $string = strtr($string, $replacements);
        }

        return $string;
    }
}
