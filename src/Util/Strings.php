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

class Strings
{
    /**
     * Returns a normalized Composer name part (vendor or module).
     *
     * @see https://getcomposer.org/doc/04-schema.md#name
     * @param string $part
     * @return string
     */
    public static function formatComposerNamePart(string $part): string
    {
        return preg_replace('/[^a-z0-9_.-]/', '-', mb_strtolower($part));
    }

    /**
     * Returns a normalized PHP namespace part
     *
     * @param string $part
     * @return string
     */
    public static function formatPhpNamespacePart(string $part): string
    {
        return preg_replace('/[^a-z0-9A-Z]/', '', ucwords($part, " \t\r\n\f\v-_."));
    }
}
