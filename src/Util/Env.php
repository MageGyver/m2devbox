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

            // @todo #security: naïvely walk $_ENV or instead walk a white-listed subset of it?
            array_walk($_ENV, function ($v, $k) use (&$replacements) {
                $replacements['$('.$k.')'] = $v;
            });

            $string = strtr($string, $replacements);
        }

        return $string;
    }
}
