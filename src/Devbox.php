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

use Dotenv\Dotenv;
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
        return <<<'BANNER'
<options=bold;fg=#f46f25>
            ____       _               _                  
 _ __ ___  |___ \   __| |  ___ __   __| |__    ___  __  __
| '_ ` _ \   __) | / _` | / _ \\ \ / /| '_ \  / _ \ \ \/ /
| | | | | | / __/ | (_| ||  __/ \ V / | |_) || (_) | >  < 
|_| |_| |_||_____| \__,_| \___|  \_/  |_.__/  \___/ /_/\_\
</>
BANNER;
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

    /**
     *  Load (or reload) ENV vars
     */
    public static function loadEnv()
    {
        $cwd = getcwd();

        // load default ENV vars form config
        $defaultEnv = Config::get('default_env');
        foreach ($defaultEnv as $key => $value) {
            if (!empty($value) && !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv($key.'='.$value);
            }
        }

        // load custom ENV vars from $CWD/.env
        if (file_exists($cwd.'/.env')) {
            $dotenv = Dotenv::createMutable($cwd);
            $dotenv->load();
        }
    }
}
