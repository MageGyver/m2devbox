<?php

namespace Devbox;

use Symfony\Component\Console\Application;

class Devbox extends Application
{
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $commands = $this->findCommands();
        $this->_addCommands($commands);

        if (in_array('Status', $commands)) {
            $this->setDefaultCommand('status');
        }
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
     * Find command class files.
     *
     * @return string[] Array of class names
     */
    protected function findCommands(): array
    {
        $commands = [];

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
     * @param string[]  $commands   Array of command class names
     */
    protected function _addCommands(array $commands): void
    {
        foreach (array_values($commands) as $commandName) {
            $commandFQN = '\\Devbox\\Command\\'.$commandName;
            $command = new $commandFQN();

            $this->add($command);
        }
    }

    /**
     * Extrapolate env variables in a string.
     *
     * @param string $string
     * @return string
     */
    public static function extrapolateEnv(string $string): string
    {
        if (preg_match('/\$\(.*\)/mU', $string) === 1) {
            $replacements = [];
            array_walk($_ENV, function ($v, $k) use (&$replacements) {
                $replacements['$('.$k.')'] = $v;
            });

            $string = strtr($string, $replacements);
        }

        return $string;
    }
}
