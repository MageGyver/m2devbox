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

use MageGyver\M2devbox\Devbox;
use MageGyver\M2devbox\Service\ModuleBoilerplate;
use MageGyver\M2devbox\Service\RecipeLoader;
use MageGyver\M2devbox\Util\Strings;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class StartModule extends Command
{
    protected static $defaultName = 'start-module';
    protected $filesystem;

    const OPTION_VENDOR = 'vendor';
    const OPTION_MODULE = 'module';
    const OPTION_PROJECT = 'project-path';
    const OPTION_PHPSTORM = 'phpstorm';
    const OPTION_MAGE_VERSION = 'mage-version';
    const OPTION_START = 'start';
    protected $io;

    protected function configure()
    {
        $this
            ->setDescription('Create a new blank Magento 2 module and optionally start m2devbox with it.')
            ->addOption(self::OPTION_VENDOR, null, InputOption::VALUE_REQUIRED, 'Module vendor name')
            ->addOption(self::OPTION_MODULE, null, InputOption::VALUE_REQUIRED, 'Module name')
            ->addOption(self::OPTION_PROJECT, null, InputOption::VALUE_REQUIRED, 'Path to module\'s root project directory')
            ->addOption(self::OPTION_PHPSTORM, null, InputOption::VALUE_NONE, 'Create JetBrains PhpStorm project')
            ->addOption(self::OPTION_MAGE_VERSION, null, InputOption::VALUE_REQUIRED, 'Magento 2 version to start with (only required when using <info>--'.self::OPTION_PHPSTORM.'</info> or <info>--'.self::OPTION_START.'</info>)')
            ->addOption(self::OPTION_START, null, InputOption::VALUE_NONE, 'Start Magento 2 instance after creating the module')
            ->setHelp('This command starts a new blank Magento 2 module and creates all the boilerplate you need to start developing it.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io           = new SymfonyStyle($input, $output);
        $filesystem         = new Filesystem();
        $boilerplateService = new ModuleBoilerplate();

        $this->io->title('Create a new Magento 2 module');
        $this->io->writeln(
            'This wizard will assist you with creating a new blank module and will create all the boilerplate ' .
            'code you need. At the end, it will start a Magento 2 instance so you can start developing right away!'
        );

        $validatorNotEmpty = function($value) {
            if (empty($value)) {
                throw new \RuntimeException('Please provide an answer.');
            }

            return $value;
        };

        $validatorDirectoryExists = function($value) use ($filesystem, $validatorNotEmpty, $boilerplateService) {
            $validatorNotEmpty($value);
            $resolvedPath = $filesystem->readlink($value, true);
            if (!$resolvedPath) {
                try {
                    $filesystem->mkdir($value);
                } catch (\Exception $e) {
                    throw new \RuntimeException(
                        sprintf(
                            '%s could be created! Please make sure to supply an absolute and writable path!',
                            $value
                        ), 0, $e
                    );
                }

                return $value;
            } else {
                if (!$boilerplateService->isDirEmpty($value)) {
                    throw new \RuntimeException(sprintf('The directory "%s" is not empty!', $value));
                }
            }

            return $value;
        };

        $vendor = $input->getOption(self::OPTION_VENDOR)
            ?? $this->io->ask(
                'Your vendor',
                null,
                $validatorNotEmpty
            );

        $moduleName = $input->getOption(self::OPTION_MODULE)
            ?? $this->io->ask(
                'Your module name',
                null,
                $validatorNotEmpty
            );

        $projectDir = $input->getOption(self::OPTION_PROJECT)
            ?? $this->askForPath(
                $input,
                $output,
                'Module project path (absolute path, needs to be empty or nonexistent)',
                getcwd(),
                $validatorDirectoryExists
            );

        $createPhpStormProject = $input->getOption(self::OPTION_PHPSTORM)
            || $this->io->confirm(
                'Create JetBrains PhpStorm project?'
            );

        $startMagentoInstance = $input->getOption(self::OPTION_START)
            || $this->io->confirm(
                'Start Magento 2 instance after creating the module?'
            );

        if ($createPhpStormProject || $startMagentoInstance) {
            $mageVersion =
                $input->getOption(self::OPTION_MAGE_VERSION)
                ?? $this->io->ask(
                    'Magento 2 version',
                    RecipeLoader::getNewest()->getVersion(),
                    $validatorNotEmpty
                    )
            ;
        }

        $boilerplateService->createModule(
            $projectDir,
            $createPhpStormProject,
            $this->getPlaceholders($vendor, $moduleName, $mageVersion ?? null)
        );

        $this->io->writeln(sprintf('<info>Successfully created module at %s</info>', $projectDir));

        if ($startMagentoInstance) {
            $running = RecipeLoader::getRunning($this->io);
            foreach ($running as $runningRecipe) {
                $runningRecipe->stop();
            }

            // reload env vars from module root directory
            chdir($projectDir);
            Devbox::loadEnv();

            $recipe = RecipeLoader::get($mageVersion, $this->io);
            $recipe->start();
        }

        return Command::SUCCESS;
    }

    protected function askForPath(
        InputInterface $input,
        OutputInterface $output,
        string $question,
        ?string $default = null,
        ?callable $validator = null
    )
    {
        $q = new Question($question, $default);
        $q->setValidator($validator);

        return $this->io->askQuestion($q);
    }

    /**
     * @param string $vendor
     * @param string $module
     * @param ?string $mageVersion
     * @return array
     * @throws \Exception
     */
    protected function getPlaceholders(string $vendor, string $module, ?string $mageVersion): array
    {
        if ($mageVersion) {
            $recipe = RecipeLoader::get($mageVersion);
        }

        $composerVendor = Strings::formatComposerNamePart($vendor);
        $composerModule = Strings::formatComposerNamePart($module);
        $vendorNamespace = Strings::formatPhpNamespacePart($vendor);
        $moduleNamespace = Strings::formatPhpNamespacePart($module);

        $placeholders =  [
            'VENDOR'            => $composerVendor,
            'MODULE'            => $composerModule,
            'VENDOR_NAMESPACE'  => $vendorNamespace,
            'MODULE_NAMESPACE'  => $moduleNamespace,
            'MODULE_NAME'       => $vendorNamespace . '_' . $moduleNamespace,
            'IDEA_PROJECT_NAME' => $composerVendor . '-' . $composerModule,
            'PHPSERVER_UUID'    => uuid_create(UUID_TYPE_RANDOM),
            'WEB_DOMAIN'        => $_ENV['M2D_MAGE_WEB_DOMAIN'],
            'WEB_PORT'          => $_ENV['M2D_WEB_PORT'],
        ];

        if ($mageVersion) {
            $placeholders = array_merge($placeholders, [
                'MAGENTO_PATH'      => $recipe->getMageSrcDir(),
                'MAGENTO_VERSION'   => $recipe->getVersion(),
                'PHP_VERSION'       => $recipe->getPhpVersion(),
                'ENV_MAGE_VERSION_DEFINITION' => 'M2D_MAGE_VERSION='.$recipe->getVersion(),
            ]);
        } else {
            $placeholders = array_merge($placeholders, [
                'ENV_MAGE_VERSION_DEFINITION' => '',
            ]);
        }

        return $placeholders;
    }
}
