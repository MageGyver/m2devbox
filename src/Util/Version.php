<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace MageGyver\M2devbox\Util;

use Exception;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function is_array;
use function array_key_exists;
use const MageGyver\M2devbox\M2D_VERSION;
use SebastianBergmann\Version as SebVersion;

class Version
{
    /**
     * Retrieve the version string of this m2devbox installation.
     *
     * @psalm-suppress UndefinedConstant
     * @return string
     */
    public static function getVersion(): string
    {
        if (!self::isInstalledAsComposerPackage()) {
            return M2D_VERSION;
        }

        try {
            $composerJson = DB_ROOT . '/composer.json';
            if (file_exists($composerJson)) {
                $version = self::extractVersionFromComposerJson(file_get_contents($composerJson));
                return (new SebVersion($version, DB_ROOT))->getVersion();
            } else {
                return '(unknown)';
            }
        } catch (Exception $e) {
            return '(unknown)';
        }
    }

    /**
     * Check, whether this installation of m2devbox is installed as a Composer
     * package or not.
     *
     * @return bool
     * @codeCoverageIgnore
     * @psalm-suppress UndefinedConstant
     */
    public static function isInstalledAsComposerPackage(): bool
    {
        // A PHAR distribution has the placeholder value of this constant replaced with a version string.
        // A composer package distribution retains the placeholder value `@ git_tag @` (without spaces).
        return M2D_VERSION === '@'.'git_tag'.'@';
    }

    /**
     * Extract the package version from a composer.json file
     *
     * @param string $composerJson
     * @return string
     * @throws Exception
     */
    public static function extractVersionFromComposerJson(string $composerJson): string
    {
        $composerJson = json_decode($composerJson, true);
        if (!is_array($composerJson) || !array_key_exists('version', $composerJson)) {
            throw new Exception('Could not extract version number from composer.json.');
        }

        return $composerJson['version'];
    }
}
