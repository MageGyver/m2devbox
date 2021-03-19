<?php declare(strict_types=1);
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace Devbox\Util;

use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use function \array_diff;
use function \scandir;
use function \pathinfo;
use function \sprintf;
use const Devbox\DB_SRC;

class CommandLoader
{
    /**
     * Find command class files.
     *
     * @return string[] Array of class names
     */
    public static function findCommands(): array
    {
        $commands = [];

        /** @psalm-suppress UndefinedConstant */
        $files = array_diff(scandir(DB_SRC.'/Command'), ['.', '..']);
        foreach ($files as $file) {
            $pi = pathinfo($file);

            if ($pi['extension'] === 'php') {
                $commands[] = $pi['filename'];
            }
        }

        return $commands;
    }

    /**
     * Add commands to Console Application.
     *
     * @param Application $application
     * @param string[]    $commands Array of command class names
     * @throws Exception
     */
    public static function addCommandsToApplication(Application $application, array $commands): void
    {
        foreach ($commands as $commandName) {
            if (!empty($commandName)) {
                $command = self::instantiateCommand($commandName);
                $application->add($command);
            }
        }
    }

    /**
     * Load a Command from its class name.
     *
     * @param string $commandName
     * @return Command
     * @throws Exception
     */
    public static function instantiateCommand(string $commandName): Command
    {
        $commandName = ucfirst($commandName);

        if (!class_exists($commandName)) {
            $commandName = '\\Devbox\\Command\\' . $commandName;

            if (!class_exists($commandName)) {
                throw new Exception(sprintf('Command class %s does not exist!', $commandName));
            }
        }

        return new $commandName();
    }
}
