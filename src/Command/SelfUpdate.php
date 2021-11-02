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

namespace MageGyver\M2devbox\Command;

use MageGyver\M2devbox\Util\Updater;
use Exception;
use MageGyver\M2devbox\Util\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use const MageGyver\M2devbox\M2D_VERSION;

class SelfUpdate extends Command
{
    /** @var string */
    protected static $defaultName = 'self-update';

    protected function configure()
    {
        $this
            ->setDescription('Updates m2devbox to the newest version.')
            ->setHelp('This command checks for available updates and installs the newest version, if any was found.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if (Version::isInstalledAsComposerPackage()) {
                $io->warning('This m2devbox is installed as a Composer package. Please run composer update magegyver/m2devbox instead.');
                return Command::FAILURE;
            } else {
                try {
                    $updater = new Updater(new Filesystem());

                    $releaseData = Updater::downloadReleaseInfo();
                    $latestReleaseInfo = Updater::extractReleaseInfo($releaseData);

                    if (Updater::isNewerVersion($latestReleaseInfo['version'])) {
                        if ($io->confirm('Update from '.M2D_VERSION.' to latest release '.$latestReleaseInfo['version'].'?')) {
                            $updater->doUpdate($latestReleaseInfo['download']);
                            $io->success('m2devbox was successfully updated to version '.$latestReleaseInfo['version']);
                        } else {
                            $io->warning('Update aborted.');
                            return Command::FAILURE;
                        }
                    } else {
                        $io->success('m2devbox '.M2D_VERSION.' is already up-to-date.');
                    }

                    return Command::SUCCESS;
                } catch (Exception $e) {
                    $io->error($e->getMessage()."\nPlease try a manual update by downloading m2devbox again!");
                    return Command::FAILURE;
                }
            }
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
