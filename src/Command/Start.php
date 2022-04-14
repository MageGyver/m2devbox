<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

/** @noinspection PhpMissingFieldTypeInspection */

namespace MageGyver\M2devbox\Command;

use Dotenv\Dotenv;
use MageGyver\M2devbox\Service\RecipeLoader;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Start extends Command
{
    protected static $defaultName = 'start';

    protected function configure()
    {
        $this
            ->setDescription('Start a Magento environment.')
            ->setHelp('This command starts the given Magento version.')
            ->addArgument('version', InputArgument::OPTIONAL, 'Magento version to start. Omit to start version defined in .env file (M2D_MAGE_VERSION entry)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $version = $input->getArgument('version');
            if ($version === null) {
                // read M2D_MAGE_VERSION from .env file
                $cwd = getcwd();

                if (!is_readable($cwd.'/.env')) {
                    throw new Exception(
                        'No Magento version was provided and no .env file found in the current working directory.'
                        .'Please provide the Magento version to start either via command argument or via .env file (M2D_MAGE_VERSION entry).'
                    );
                }

                $env = Dotenv::parse(file_get_contents($cwd.'/.env'));
                if (!array_key_exists('M2D_MAGE_VERSION', $env) || trim($env['M2D_MAGE_VERSION']) === '') {
                    throw new Exception(
                        'No Magento version was provided and no M2D_MAGE_VERSION entry was found in the .env file.'
                        .'Please provide the Magento version to start either via command argument or via .env file (M2D_MAGE_VERSION entry).'
                    );
                }

                $version = $env['M2D_MAGE_VERSION'];
            }

            $running = RecipeLoader::getRunning($io);

            // remove version to be started from $running array
            $remainingRunning = array_filter(
                $running,
                function($key) use ($version) {
                    return ($key !== $version);
                },
                ARRAY_FILTER_USE_KEY
            );

            // stop all the remaining running instances
            foreach ($remainingRunning as $recipe) {
                $recipe->stop();
            }

            $recipe = RecipeLoader::get($version, $io);
            $recipe->start();

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
