<?php declare(strict_types=1);
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace Util;

use Devbox\Devbox;
use Devbox\Util\CommandLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

/**
 * @covers \Devbox\Util\CommandLoader
 */
class CommandLoaderTest extends TestCase
{

    public function testInstantiateInvalidCommand()
    {
        $this->expectException(\Exception::class);
        CommandLoader::instantiateCommand('<invalid command name>');
    }

    /**
     * @uses \Devbox\Command\Status
     */
    public function testInstantiateCommandLowercase()
    {
        $command = CommandLoader::instantiateCommand('status');
        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * @uses \Devbox\Command\Status
     */
    public function testInstantiateCommandUppercase()
    {
        $command = CommandLoader::instantiateCommand('Status');
        $this->assertInstanceOf(Command::class, $command);
    }

    public function testAddCommandsToApplication()
    {
        $appMock = $this->createMock(Devbox::class);
        CommandLoader::addCommandsToApplication($appMock, ['Start', 'Stop', 'Clear']);

        $this->markTestIncomplete(
            'testAddCommandsToApplication() has not been implemented yet.'
        );
    }

    public function testFindCommands()
    {
        $commands = CommandLoader::findCommands();
        $this->assertEqualsCanonicalizing(
            [
                'Clear',
                'Cli',
                'Compose',
                'SelfUpdate',
                'Start',
                'Status',
                'Stop',
            ],
            $commands
        );
    }
}
