<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace MageGyver\M2devbox;

use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

interface RecipeInterface
{
    /**
     * Set recipe instance configuration.
     *
     * @param array $config
     * @return AbstractRecipe
     */
    public function configure(array $config): self;

    /**
     * Get Magento long version string.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the Magento version-specific installation source directory on the host system.
     *
     * @return string
     * @throws Exception
     */
    public function getMageSrcDir(): string;

    /**
     * Get Magento short version identifier string.
     *
     * @return string
     */
    public function getShortVersion(): string;

    /**
     * Get the PHP version for use with this Magento environment.
     *
     * @return string
     */
    public function getPhpVersion(): string;

    /**
     * Get the PHP Docker image version for use with this Magento environment.
     *
     * @return string
     */
    public function getPhpImageVersion(): string;

    /**
     * Set SimfonyStyle instance to this Recipe (for shell output).
     *
     * @param SymfonyStyle|null $io
     * @return $this
     */
    public function setIo(?SymfonyStyle $io): self;

    /**
     * Start Magento environment.
     *
     * @throws Exception
     */
    public function start(): void;

    /**
     * Stop Magento environment.
     */
    public function stop(): void;

    /**
     * Clear Magento environment. That is, delete all persisted files
     * associated with this version.
     */
    public function clear(): void;

    /**
     * Check, whether the Docker images for Magento environment were already
     * built.
     *
     * @return bool
     */
    public function isBuilt(): bool;

    /**
     * Check, whether this Magento version is currently running.
     *
     * @return bool
     */
    public function isRunning(): bool;

    /**
     * Execute a Docker Compose command
     *
     * @param string|array $arguments           Docker Compose arguments to execute (ex. "run" or ["up", "-d"])
     * @param string|null  $output              (Optional) Command output
     * @param bool         $showOutputInSpinner Show live command output in spinner status line
     * @param bool         $allocateTty         Allocate a TTY (useful for things like "docker-compose exec web bash")
     * @param array        $env                 ENV variables for the process
     * @return int|null                 Exit code of the process
     * @throws Exception
     */
    public function dockerCompose($arguments, ?string &$output = null, bool $showOutputInSpinner = true, bool $allocateTty = false, array $env = []): ?int;
}
