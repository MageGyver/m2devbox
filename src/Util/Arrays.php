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

class Arrays
{
    /**
     * Converts [K => V, K => V] to [[K, V], [K, V]]
     */
    public static function keyValue2ValuePair(array $array): array
    {
        array_walk(
            $array,
            function (&$value, $key) {
                $value = [$key, $value];
            }
        );

        return $array;
    }

    /**
     * Implode an array of value pairs:
     *
     * implodeValuePairs('=', ', ', [['foo', 'bar'], ['bao', 'baz']])
     * => "foo=bar, bao=baz"
     *
     * @param string $valueGlue
     * @param string $pairGlue
     * @param array  $array
     * @return string
     */
    public static function implodeValuePairs(string $valueGlue, string $pairGlue, array $array): string
    {
        array_walk(
            $array,
            function (&$value) use ($valueGlue) {
                $value = implode($valueGlue, $value);
            }
        );

        return implode($pairGlue, $array);
    }
}
