<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace MageGyver\M2devbox;

use MageGyver\M2devbox\Service\Config;
use MageGyver\M2devbox\Util\CommandLoader;
use Exception;
use Symfony\Component\Console\Application;

class Devbox extends Application
{
    /**
     * Devbox constructor.
     *
     * @param string $name
     * @param string $version
     * @throws Exception
     */
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        register_shutdown_function(function() {
            // make sure we show the terminal cursor, in case it was hidden before
            echo "\033[?25h";
        });

        $commands = CommandLoader::findCommands();
        CommandLoader::addCommandsToApplication($this, $commands);

        if (in_array('Status', $commands)) {
            $this->setDefaultCommand('status');
        }

        $this->exportDockerConfig();
    }

    /**
     * Gets the help message.
     *
     * @return string A help message
     */
    public function getHelp(): string
    {

        return '<options=bold;fg=#f46f25>
                                          .d8888b.       888
                                         d88P  Y88b      888
                                                888      888
88888b.d88b.   8888b.   .d88b.   .d88b.       .d88P  .d88888  .d88b.  888  888
888 "888 "88b     "88b d88P"88b d8P  Y8b  .od888P"  d88" 888 d8P  Y8b 888  888
888  888  888 .d888888 888  888 88888888 d88P"      888  888 88888888 Y88  88P
888  888  888 888  888 Y88b 888 Y8b.     888"       Y88b 888 Y8b.      Y8bd8P
888  888  888 "Y888888  "Y88888  "Y8888  8888888888  "Y88888  "Y8888    Y88P
                            888
                       Y8b d88P
                        "Y88P"</>';
    }

    /**
     * Copy Docker config files from PHAR to global config dir.
     * This is needed, because external programs like Docker can't access files
     * stored in a PHAR.
     *
     * @throws Exception
     */
    protected function exportDockerConfig()
    {
        /** @psalm-suppress UndefinedConstant */
        $localDockerConfigAssetsDir = DB_ROOT.'/docker/';
        $dockerConfigDir = Config::getDockerConfigDir();

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($localDockerConfigAssetsDir));
        $it->rewind();
        while ($it->valid()) {
            if (!$it->isDot()) {
                $targetFile = $dockerConfigDir.'/'.$it->getSubPathName();

                // only overwrite existing file if it's content differs
                if (file_exists($targetFile)) {
                    $localHash = hash('crc32c', file_get_contents($it->key()));
                    $targetHash = hash('crc32c', file_get_contents($targetFile));

                    if ($localHash !== $targetHash) {
                        @copy($it->key(), $targetFile);
                    }
                } else {
                    @mkdir(dirname($targetFile), 0755, true);
                    @copy($it->key(), $targetFile);
                }
            }

            $it->next();
        }
    }
}
