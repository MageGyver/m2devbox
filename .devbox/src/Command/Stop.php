<?php

namespace Devbox\Command;

use Devbox\Recipe\Mage241;
use Devbox\Service\Config;
use Devbox\Service\RecipeLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Stop extends Command
{
    protected static $defaultName = 'stop';

    protected function configure()
    {
        $this
            ->setDescription('Stop a Magento environment.')
            ->setHelp('This command stops the running Magento version.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $recipes = RecipeLoader::getRunning( $io);

            if (empty($recipes)) {
                $io->writeln('<comment>No Magento version is currently running.</comment>');
                return Command::SUCCESS;
            }

            foreach ($recipes as $recipe) {
                $recipe->stop();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
