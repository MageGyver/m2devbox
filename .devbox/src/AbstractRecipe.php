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
    protected array $config = [];

    protected ?SymfonyStyle $io;
    protected Filesystem $filesystem;

    public function __construct(?SymfonyStyle $io = null)
    {
        $this->io = $io;
        $this->filesystem = new Filesystem();
    }

    /**
     * Set recipe instance configuration.
     *
     * @param array $config
     * @return AbstractRecipe
     */
    public function configure(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    abstract protected function getExpectedContainers(): array;

    protected function getBaseDockerComposeFiles(): array
    {
        return [$this->getDockerFilename('docker-compose.yml')];
    }

    protected function getVersionSpecificDockerComposeFiles(): array
    {
        return [$this->getDockerFilename('docker-compose.mage-'.$this->getVersion().'.yml')];
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

    public function getVersion(): string
    {
        return $this->config['long_version'];
    }

    public function getShortVersion(): string
    {
        return $this->config['short_version'];
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

    public function start(): void
    {
        if ($this->isRunning()) {
            $this->status(
                Devbox::extrapolateEnv(
                    '<info>Magento '.$this->getVersion().' is already up & running at '.
                    'http://$(MAGE_WEB_DOMAIN):$(DOCKER_WEB_PORT)/admin (User: '.
                    '$(MAGE_ADMIN_USER), password: $(MAGE_ADMIN_PASS))</info>'
                )
            );
            return;
        }

        $this->installMagento();

        $this->status('<info>Starting Magento %s...</info>', [$this->getVersion()]);
        $this->inDocker(
            'web',
            'chown -R www-data:www-data /var/www/html/',
            [$this->getMageSrcDir(), '/var/www/html']
        );
        $this->dockerComposeUp(true);

        $this->status(
            Devbox::extrapolateEnv(
                '<info>Magento '.$this->getVersion().' is now up & running at '.
                'http://$(MAGE_WEB_DOMAIN):$(DOCKER_WEB_PORT)/admin (User: '.
                '$(MAGE_ADMIN_USER), password: $(MAGE_ADMIN_PASS))</info>'
            )
        );
    }

    abstract protected function installMagento();

    abstract protected function emptyDb();

    public function stop(): void
    {
        if (!$this->isRunning()) {
            $this->status('<info>Magento %s is already stopped.</info>', [$this->getVersion()]);
            return;
        }

        $this->status('<info>Stopping Magento %s...</info>', [$this->getVersion()]);
        $this->dockerComposeStop();

        $this->status(
            '<info>Magento '.$this->getVersion().' is now stopped.</info>'
        );
    }

    public function clear(): void
    {
        if (posix_getuid() !== 0) {
            $this->status('<error>Please run this command with root privileges!</error>');
            $this->status(
                '<comment>Clearing Magento source files from the mounted directory is not possible without '.
                'root privileges because these files are owned by another user.</comment>'
            );
            return;
        }

        $this->status('<info>Clearing Magento %s...</info>', [$this->getVersion()]);
        $this->stop();
        $this->emptyDb();

        $dir = $this->getMageSrcDir();
        if ($this->filesystem->exists($dir)) {
            $this->filesystem->remove($dir);
        }
    }

    protected function getState(string $key)
    {
        return State::get('mage_'.$this->getVersion().'.'.$key);
    }

    protected function setState(string $key, $value)
    {
        State::set('mage_'.$this->getVersion().'.'.$key, $value);
    }

    public function isRunning(): bool
    {
        return $this->getState('mage_running') === true;
    }

    /**
     * @param array        $commandLine         Command line to execute (one array element per command argument)
     * @param string|null &$output              (Optional) Command output
     * @param bool         $showOutputInSpinner Show current output line next to spinner animation
     * @param bool         $allocateTty         Allocate a tty
     * @return int|null    Exit code of the command
     */
    public function exec(array $commandLine, string &$output = null, bool $showOutputInSpinner = true, bool $allocateTty = false): ?int
    {
        $io = $this->io;
        $spinner = null;

        if ($io->isVerbose()) {
            $io->writeln('<comment>[executing]</comment> '.implode(' ', $commandLine));
        } elseif (!$allocateTty) {
            $spinner = new Spinner();
            $spinner->begin();
        }

        $callback = function ($type, $buffer) use ($io, $spinner, $showOutputInSpinner) {
            if ($io->isVerbose()) {
                $io->writeln("<info>\t" . strtoupper($type) . '</info> > ' . $buffer);
            } else if ($spinner !== null && $showOutputInSpinner) {
                $spinner->setMessage($buffer);
            }
        };

        $p = new Process($commandLine);
        $p
            ->setTty($allocateTty)
            ->setTimeout(3600)
            ->setIdleTimeout(600)
            ->start($callback)
        ;

        if (!$io->isVerbose() && $spinner !== null) {
            while ($p->isRunning()) {
                $spinner->spin();
            }
        }

        if (!$io->isVerbose() && $spinner !== null) {
            $spinner->end();
        } else {
            $p->wait($callback);
        }

        if (!$allocateTty && !$p->isSuccessful()) {
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
     * @param string|array $commands
     * @param string|null     $output (Optional) Command output
     * @param bool            $showOutputInSpinner
     * @param bool            $allocateTty
     * @return int|null
     */
    public function dockerCompose($commands, string &$output = null, bool $showOutputInSpinner = true, bool $allocateTty = false): ?int
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
        return $this->exec($commandLine, $output, $showOutputInSpinner, $allocateTty);
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
        /** @psalm-suppress UndefinedConstant */
        return DB_ROOT.'/mage_src/'.$this->getVersion().'/';
    }

    /**
     * @return string
     */
    protected function getAppCodeDir(): string
    {
        /** @psalm-suppress UndefinedConstant */
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
        /** @psalm-suppress UndefinedConstant */
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
