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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Compose extends Command
{
    protected static $defaultName = 'compose';

    protected function configure()
    {
        $this
            ->setDescription('Run a Docker Compose command.')
            ->setHelp(
                "This command runs the given Docker Compose command. Pro tip: Supply Docker Compose commands after a double dash to avoid ambiguous arguments!\nExample:\n\n".
                "    <info>".$_SERVER['PHP_SELF'] . " compose 2.4.1 -- ps -a</info>\n"
            )
            ->addOption('tty', 't', InputOption::VALUE_NONE, 'Allocate a tty')
            ->addArgument('version', InputArgument::REQUIRED, 'Magento version to start')
            ->addArgument('dc-command', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'Docker Compose command (for example: "up")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $recipe = RecipeLoader::get($input->getArgument('version'), $io);
            $recipe->dockerCompose($input->getArgument('dc-command'), $commandOutput, false, $input->getOption('tty'));

            echo $commandOutput;

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
