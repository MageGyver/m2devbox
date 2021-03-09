<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace Devbox;

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
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get Magento short version identifier string.
     * @return string
     */
    public function getShortVersion(): string;
    public function setIo(?SymfonyStyle $io): self;
    public function start(): void;
    public function stop(): void;
    public function clear(): void;
    public function isBuilt(): bool;
    public function isRunning(): bool;

    /**
     * @param string|array $commands
     * @param string|null     $output (Optional) Command output
     * @param bool            $showOutputInSpinner
     * @param bool            $allocateTty
     * @return int|null
     */
    public function dockerCompose($commands, string &$output = null, bool $showOutputInSpinner = true, bool $allocateTty = false): ?int;
}
