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

use MageGyver\M2devbox\RecipeInterface;
use MageGyver\M2devbox\Service\RecipeLoader;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Clear extends Command
{
    /** @var string */
    protected static $defaultName = 'clear';

    protected function configure()
    {
        $this
            ->setDescription('Clear a Magento environment.')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Assume "yes" as answer to all questions.')
            ->addArgument('versions', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'Magento versions to clear. Omit to clear all versions.')
            ->setHelp('This command clears the given Magento versions and removes it from the system.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $versions = $input->getArgument('versions');

            if (!empty($versions)) {
                $recipes = RecipeLoader::getMultiple($versions, $io);
                $confirmText = sprintf(
                    'Are you sure you want to clear these Magento environments: %s?',
                    implode(', ', array_keys($recipes))
                );
            } else {
                $recipes = RecipeLoader::getAll($io);
                $confirmText = 'Are you sure you want to clear <error>all</error> Magento environments?';
            }

            if ($input->getOption('yes') || $io->confirm($confirmText, false)) {
                foreach ($recipes as $recipe) {
                    $recipe->clear();
                }
            } else {
                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
