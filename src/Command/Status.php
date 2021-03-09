<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Devbox\Command;

use Devbox\Service\Config;
use Devbox\Service\RecipeLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Status extends Command
{
    protected static $defaultName = 'status';

    protected function configure()
    {
        $this
            ->setDescription('Show an overall status.')
            ->setHelp('This command shows status information for each supported Magento 2 dev boxes.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->writeln('<fg=#f46f25>mage2devbox status overview:</>');
        $io->newLine();

        $true = '<fg=green>True</>';
        $false = '<fg=red>False</>';

        $tableRows = [];

        $recipes = Config::getRecipes();
        foreach ($recipes as $version => $versionConfig) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $instance = RecipeLoader::get($version, $io);

            $tableRows[] = [
                '<options=bold>'.$version.'</>',
                $instance->isBuilt() ? $true : $false,
                $instance->isRunning() ? $true : $false
            ];
        }

        $table = new Table($output);
        $table
            ->setStyle('box')
            ->setHeaders(['Magento Version', 'Is built', 'Is running'])
            ->setRows($tableRows)
            ->render()
        ;

        $io->newLine();
        $io->writeln('For usage information, run <info>'.$_SERVER['PHP_SELF'].' help</info>');

        return Command::SUCCESS;
    }
}