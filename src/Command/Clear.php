<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Devbox\Command;

use Devbox\Service\RecipeLoader;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('version', InputArgument::OPTIONAL, 'Magento version to clear. Omit to clear all versions.')
            ->setHelp('This command clears the given Magento version and removes it from the system.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $version = $input->getArgument('version');

            if ($version !== null) {
                $recipes = [RecipeLoader::get($version, $io)];
            } else {
                $recipes = RecipeLoader::getAll($io);
            }

            if ($io->confirm('Are you sure you want to clear the given environments?', false)) {
                foreach ($recipes as $recipe) {
                    $recipe->clear();
                    return Command::SUCCESS;
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
