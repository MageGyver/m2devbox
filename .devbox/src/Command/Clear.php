<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Devbox\Command;

use Devbox\Service\RecipeLoader;
use Exception;
use Symfony\Component\Console\Command\Command;
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
            ->setHelp('This command clears the given Magento version and removes it from the system.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if (posix_getuid() !== 0) {
                $io->writeln('<error>Please run this command with root privileges!</error>');
                $io->writeln(
                    '<comment>Clearing Magento source files from the mounted directory is not possible without '.
                    'root privileges because these files are owned by another user.</comment>'
                );

                return Command::FAILURE;
            }


            $recipes = RecipeLoader::getAll($io);

            foreach ($recipes as $recipe) {
                $recipe->clear();
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
