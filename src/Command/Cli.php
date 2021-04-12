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

use MageGyver\M2devbox\Service\RecipeLoader;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Cli extends Command
{
    protected static $defaultName = 'cli';

    protected function configure()
    {
        $this
            ->setDescription('Enter into a bash shell to run CLI commands inside the running container.')
            ->setHelp("This commands opens up a bash shell inside your container, so you can run arbitrary commands.")
            ->addArgument('container', InputArgument::OPTIONAL, 'Container to enter', 'web')
            ->addArgument('clicommand', InputArgument::OPTIONAL, 'Command to start', 'bash')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $recipe = RecipeLoader::getRunning($io);

            if (!$recipe || empty($recipe)) {
                throw new \Exception('No instance is currently running! Please start one first.');
            }

            $recipeVersion = array_key_first($recipe);

            $recipe[$recipeVersion]->dockerCompose(
                [
                    'exec',
                    $input->getArgument('container'),
                    $input->getArgument('clicommand'),
                ],
                $commandOutput,
                false,
                true
            );

            //echo $commandOutput;

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
