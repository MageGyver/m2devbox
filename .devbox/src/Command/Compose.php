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

class Compose extends Command
{
    protected static $defaultName = 'compose';

    protected function configure()
    {
        $this
            ->setDescription('Run a Docker Compose command.')
            ->setHelp(
                "This command runs the given Docker Compose command. Pro tip: Supply Docker Compose commands after a double dash to avoid ambiguous commands!\nExample:\n\n".
                "    <info>".$_SERVER['PHP_SELF'] . "compose 2.4.1 -- ps -a</info>\n"
            )
            ->addArgument('version', InputArgument::REQUIRED, 'Magento version to start')
            ->addArgument('dc-command', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'Docker Compose command (for example: "up")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $recipe = RecipeLoader::get($input->getArgument('version'), $io);
            $recipe->dockerCompose($input->getArgument('dc-command'), $commandOutput);

            echo $commandOutput;

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
