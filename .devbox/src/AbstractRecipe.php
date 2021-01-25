<?php

namespace Devbox;

use AlecRabbit\Snake\Spinner;
use Devbox\Service\State;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractRecipe implements RecipeInterface
{
    const VERSION = 'MISSINGNO';

    protected ?SymfonyStyle $io;
    protected Filesystem $filesystem;

    public function __construct(?SymfonyStyle $io = null)
    {
        $this->io = $io;
        $this->filesystem = new Filesystem();
    }

    abstract protected function getExpectedContainers(): array;

    protected function getBaseDockerComposeFiles(): array
    {
        return [$this->getDockerFilename('docker-compose.yml')];
    }

    protected function getVersionSpecificDockerComposeFiles(): array
    {
        return [$this->getDockerFilename('docker-compose.mage-'.static::getVersion().'.yml')];
    }

    protected function getDockerComposeFiles(): array
    {
        return array_merge(
            $this->getBaseDockerComposeFiles(),
            $this->getVersionSpecificDockerComposeFiles(),
        );
    }

    public function setIo(?SymfonyStyle $io): self
    {
        $this->io = $io;
        return $this;
    }

    public static function getVersion(): string
    {
        return static::VERSION;
    }

    public function isBuilt(): bool
    {
        $this->dockerCompose(
            ['ps', '-a'],
            $output,
            false
        );

        if (is_string($output) && !empty($output)) {
            $output = explode("\n", $output);
            array_splice($output, 0, 2);

            $expectedContainers = array_fill_keys($this->getExpectedContainers(), false);

            foreach ($output as $line) {
                $line = preg_split('/\s+/', $line);
                if (count($line) > 0) {
                    $container = $line[0];
                    if (array_key_exists($container, $expectedContainers)) {
                        $expectedContainers[$container] = true;
                    }
                }
            }

            foreach ($expectedContainers as $container => $containerIsBuilt) {
                if (!$containerIsBuilt) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    public function start()
    {
        if ($this->isRunning()) {
            $this->status(
                Devbox::extrapolateEnv(
                    '<info>Magento '.static::getVersion().' is already up & running at '.
                    'http://$(MAGE_WEB_DOMAIN):$(DOCKER_WEB_PORT)/admin (User: '.
                    '$(MAGE_ADMIN_USER), password: $(MAGE_ADMIN_PASS))</info>'
                )
            );
            return;
        }

        $this->installMagento();

        $this->status('<info>Starting Magento %s...</info>', [static::getVersion()]);
        $this->inDocker(
            'web',
            'chown -R www-data:www-data /var/www/html/',
            [$this->getMageSrcDir(), '/var/www/html']
        );
        $this->dockerComposeUp(true);

        $this->status(
            Devbox::extrapolateEnv(
                '<info>Magento '.static::getVersion().' is now up & running at '.
                'http://$(MAGE_WEB_DOMAIN):$(DOCKER_WEB_PORT)/admin (User: '.
                '$(MAGE_ADMIN_USER), password: $(MAGE_ADMIN_PASS))</info>'
            )
        );
    }

    abstract protected function installMagento();

    abstract protected function emptyDb();

    public function stop()
    {
        if (!$this->isRunning()) {
            $this->status('<info>Magento %s is already stopped.</info>', [static::getVersion()]);
            return;
        }

        $this->status('<info>Stopping Magento %s...</info>', [static::getVersion()]);
        $this->dockerComposeStop();

        $this->status(
            '<info>Magento '.static::getVersion().' is now stopped.</info>'
        );
    }

    public function clear()
    {
        if (posix_getuid() !== 0) {
            $this->status('<error>Please run this command with root privileges!</error>');
            $this->status(
                '<comment>Clearing Magento source files from the mounted directory is not possible without '.
                'root privileges because these files are owned by another user.</comment>'
            );
            return;
        }

        $this->status('<info>Clearing Magento %s...</info>', [static::getVersion()]);
        $this->stop();
        $this->emptyDb();

        $dir = $this->getMageSrcDir();
        if ($this->filesystem->exists($dir)) {
            $this->filesystem->remove($dir);
        }
    }

    protected function getState(string $key)
    {
        return State::get('mage_'.static::getVersion().'.'.$key);
    }

    protected function setState(string $key, $value)
    {
        State::set('mage_'.static::getVersion().'.'.$key, $value);
    }

    public function isRunning(): bool
    {
        return $this->getState('mage_running') === true;
    }

    /**
     * @param array        $commandLine Command line to execute (one array element per command argument)
     * @param string|null &$output      (Optional) Command output
     * @param bool         $showOutputInSpinner Show current output line next to spinner animation
     * @return int                  Exit code of the command
     */
    public function exec(array $commandLine, string &$output = null, bool $showOutputInSpinner = true): int
    {
        $io = $this->io;
        $spinner = null;

        if ($io->isVerbose()) {
            $io->writeln('<comment>[executing]</comment> '.implode(' ', $commandLine));
        } else {
            $spinner = new Spinner();
            $spinner->begin();
        }

        $callback = function ($type, $buffer) use ($io, $spinner, $showOutputInSpinner) {
            if ($io->isVerbose()) {
                $io->writeln("<info>\t" . strtoupper($type) . '</info> > ' . $buffer);
            } else if ($spinner !== null && $showOutputInSpinner) {
                $spinner->setMessage($buffer);
            };
        };

        $p = new Process($commandLine);
        $p
            ->setTimeout(3600)
            ->setIdleTimeout(600)
            ->start($callback)
        ;

        while ($p->isRunning()) {
            $spinner->spin();
        }

        if (!$io->isVerbose()) {
            $spinner->end();
        } else {
            $p->wait($callback);
        }

        if (!$p->isSuccessful()) {
            throw new ProcessFailedException($p);
        }

        $output = $p->getOutput();

        return $p->getExitCode();
    }
    
    protected function dockerComposeUp(bool $detach = false)
    {
        $this->dockerCompose($detach ? ['up', '-d'] : 'up');
        $this->setState('mage_running', true);
    }

    protected function dockerComposeStop()
    {
        $this->dockerCompose('stop');
        $this->setState('mage_running', false);
    }

    /**
     * @param string|string[] $commands
     * @param string|null     $output (Optional) Command output
     * @param bool            $showOutputInSpinner
     * @return int
     */
    public function dockerCompose($commands, string &$output = null, bool $showOutputInSpinner = true): int
    {
        // shell command to execute
        $commandLine = ['docker-compose'];

        // add Docker Compose YML files via -f
        $composeFiles = $this->getDockerComposeFiles();
        foreach ($composeFiles as $file) {
            array_push($commandLine, '-f', $file);
        }

        // add Docker Compose command to shell command line
        if (is_string($commands)) {
            $commands = [$commands];
        }
        array_push($commandLine, ...$commands);

        // execute command line
        return $this->exec($commandLine, $output, $showOutputInSpinner);
    }

    /**
     * Run a command in a Docker container.
     *
     * @param string        $container    Container name
     * @param string        $command      Command to run
     * @param string[]|null $mountVolume  Optional volume mount ['/host/dir', '/container/dir']
     * @param bool          $noDeps
     */
    protected function inDocker(string $container, string $command, array $mountVolume = null, bool $noDeps = true)
    {
        $commands = ['run'];

        if ($mountVolume) {
            array_push(
                $commands,
                '-v', $mountVolume[0].':'.$mountVolume[1].':cached'
            );
        }

        if ($noDeps) {
            array_push($commands,'--no-deps');
        }

        array_push($commands,
            '--rm',
            $container,
            '/bin/bash', '-c',
            $command
        );

        $this->dockerCompose($commands);
    }

    /**
     * @return string
     */
    protected function getMageSrcDir(): string
    {
        return DB_ROOT.'/mage_src/'.static::getVersion().'/';
    }

    /**
     * @return string
     */
    protected function getAppCodeDir(): string
    {
        return DB_ROOT.'/app_code/';
    }

    protected function createMageSrcDir()
    {
        $dir = $this->getMageSrcDir();
        if (!is_dir($dir)) {
            mkdir($dir,0744, true);
        }
    }

    /**
     * Returns a full path to the specified file name in the Docker config directory.
     *
     * @param string $file
     * @return string
     */
    protected function getDockerFilename(string $file): string
    {
        return DOCKER_CONFIGS.'/'.ltrim($file, '/');
    }

    /**
     * Returns a full path to the specified file name in the current recipe version's Magento source directory.
     *
     * @param string $file
     * @return string
     */
    protected function getMageFilename(string $file): string
    {
        return $this->getMageSrcDir().ltrim($file, '/');
    }

    protected function mageFileExists(string $file): bool
    {
        return file_exists($this->getMageFilename($file));
    }

    protected function mageDirExists(string $file): bool
    {
        return is_dir($this->getMageFilename($file));
    }

    protected function status(?string $message, ?array $args = null)
    {
        if ($message === null) {
            return;
        }

        if ($this->io) {
            if (!empty($args)) {
                $message = vsprintf($message, $args);
            }

            $this->io->writeln($message);
        }
    }
}
