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

class Start extends Command
{
    protected static $defaultName = 'start';

    protected function configure()
    {
        $this
            ->setDescription('Start a Magento environment.')
            ->setHelp('This command starts the given Magento version.')
            ->addArgument('version', InputArgument::REQUIRED, 'Magento version to start')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $version = $input->getArgument('version');

            $running = RecipeLoader::getRunning($io);

            // remove current version element from array
            $remainingRunning = array_filter(
                $running,
                function($key) use ($version) {
                    return ($key !== $version);
                },
                ARRAY_FILTER_USE_KEY
            );

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
