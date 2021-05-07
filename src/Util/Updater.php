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
use Phar;
use Symfony\Component\Filesystem\Filesystem;
use function is_writable;
use function json_decode;
use function is_array;
use function array_key_exists;
use function version_compare;
use function fileperms;
use const MageGyver\M2devbox\M2D_VERSION;

class Updater
{
    const RELEASE_INFO_URL = 'https://api.github.com/repos/magegyver/m2devbox/releases/latest';

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Check, whether this installation of m2devbox is installed as a Composer
     * package or not.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public static function isInstalledAsComposerPackage(): bool
    {
        // A PHAR distribution has the placeholder value of this constant replaced with a version string.
        // A composer package distribution retains the placeholder value `@ git_tag @` (without spaces).
        return M2D_VERSION === '@'.'git_tag'.'@';
    }

    /**
     * Extract the release tag name and PHAR download URL from a Github release info JSON string.
     *
     * @param string $releaseDataJson
     * @return array
     * @throws Exception
     */
    public static function extractReleaseInfo(string $releaseDataJson): array
    {
        $releaseData = json_decode($releaseDataJson, true);
        if (!is_array($releaseData) || !array_key_exists('tag_name', $releaseData)) {
            throw new Exception('Could not extract latest version number from release info.');
        }

        if (!array_key_exists('assets', $releaseData) || empty($releaseData['assets'])) {
            throw new Exception('Could not extract latest version PHAR URL from release info.');
        }

        $asset = array_pop($releaseData['assets']);
        if (
            !is_array($asset)
            || !array_key_exists('browser_download_url', $asset)
            || !array_key_exists('name', $asset)
            || $asset['name'] !== 'm2devbox.phar'
        ) {
            throw new Exception('Could not extract latest version PHAR URL from release info.');
        }

        return [
            'version' => $releaseData['tag_name'],
            'download' => $asset['browser_download_url']
        ];
    }

    /**
     * Check, whether an update is needed, based on the given version strings.
     *
     * @param string $latestReleaseVersion
     * @param string $currentVersion
     * @return bool
     */
    public static function isNewerVersion(string $latestReleaseVersion, string $currentVersion = M2D_VERSION): bool
    {
        return version_compare($latestReleaseVersion, $currentVersion, '>');
    }

    /**
     * Download latest release information.
     *
     * @param string $url   URL to download release info from.
     * @return string
     * @throws Exception
     */
    public static function downloadReleaseInfo(string $url = self::RELEASE_INFO_URL): string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_HEADER         => 0,
            CURLOPT_VERBOSE        => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => 'm2devbox updater (curl-php)',
            CURLOPT_URL            => $url,
        ]);

        /** @var string|false $data */
        $data = curl_exec($curl);

        if ($data === false) {
            throw new Exception('Could not retrieve release data from '.$url.'. Error: '.curl_error($curl));
        }

        curl_close($curl);

        return $data;
    }

    /**
     * Download a new release from the given URL and replace the currently running
     * PHAR file with the downloaded one.
     *
     * @param string $newReleaseUrl
     * @throws Exception
     * @codeCoverageIgnore
     */
    public function doUpdate(string $newReleaseUrl): void
    {
        $pharFile = Phar::running(false);
        $perms = @fileperms($pharFile);

        if (!@is_writable($pharFile)) {
            throw new Exception('PHAR file is not writable.');
        }

        $downloadDestination = $this->filesystem->tempnam(sys_get_temp_dir(), 'm2d_', '.phar');
        $this->filesystem->copy($newReleaseUrl, $downloadDestination, true);
        $this->filesystem->rename($downloadDestination, $pharFile, true);
        $this->filesystem->chmod($pharFile, $perms);
    }
}
