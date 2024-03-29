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

use Aeno\SlickProgress\Colors;
use Aeno\SlickProgress\Progress;
use Aeno\SlickProgress\Theme\Snake;
use Aeno\SlickProgress\ThemeInterface;
use MageGyver\M2devbox\Service\Config;
use MageGyver\M2devbox\Util\Env;
use MageGyver\M2devbox\Util\Arrays;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractRecipe implements RecipeInterface
{
    protected array $config = [];

    protected ?SymfonyStyle $io;
    protected Filesystem $filesystem;

    protected ?bool $_dockerSupportsBuildKit = null;

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

    /**
     * Get an array of Docker container names that are associated with this
     * Recipe. These containers are expected to exist after a successful build.
     *
     * @return string[]
     */
    abstract protected function getExpectedContainers(): array;

    /**
     * Get basic Docker Compose files to use with docker-compose commands.
     *
     * @return string[] Array of absolute file paths
     * @throws Exception
     */
    protected function getBaseDockerComposeFiles(): array
    {
        return [$this->getDockerFilename('docker-compose.yml')];
    }

    /**
     * Get Docker Compose files to use with docker-compose commands specific
     * to this Recipe version.
     *
     * @return string[] Array of absolute file paths
     * @throws Exception
     */
    protected function getVersionSpecificDockerComposeFiles(): array
    {
        $files = $this->config['compose_files'];
        array_walk($files, function(&$filename) {
            $filename = $this->getDockerFilename($filename);
        });

        return $files;
    }

    /**
     * Get an array of Docker Compose files for use with "docker-compose -f".
     *
     * @return string[]
     * @throws Exception
     */
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

    public function getPhpVersion(): string
    {
        return $this->config['php_version'];
    }

    public function getPhpImageVersion(): string
    {
        return $this->config['php_img_version'];
    }

    public function getElasticVersion(): ?string
    {
        return $this->config['elastic_version'] ?? null;
    }

    /**
     * @throws Exception
     */
    public function isBuilt(): bool
    {
        $this->exec(
            ['docker', 'ps', '-a', '-f', 'name=m2devbox', '--format', '{{.Names}}'],
            [],
            $output,
            false
        );

        if (is_string($output) && !empty($output)) {
            $output = explode("\n", $output);

            $expectedContainers = array_fill_keys($this->getExpectedContainers(), false);

            foreach ($output as $container) {
                if (array_key_exists($container, $expectedContainers)) {
                    $expectedContainers[$container] = true;
                }
            }

            foreach ($expectedContainers as $containerIsBuilt) {
                if (!$containerIsBuilt) {
                    return false;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @noinspection HttpUrlsUsage
     */
    public function start(): void
    {
        $this->status('<info>▶️  Starting Magento %s...</info>', [$this->getVersion()]);

        if ($this->isRunning()) {
            $this->status(
                Env::extrapolateEnv(
                    '<info>✅ Magento '.$this->getVersion().' is already up & running at '.
                    'http://$(M2D_MAGE_WEB_DOMAIN):$(M2D_WEB_PORT)/admin (User: '.
                    '$(M2D_MAGE_ADMIN_USER), password: $(M2D_MAGE_ADMIN_PASS))</info>'
                )
            );
            return;
        }

        if (!$this->isBuilt()) {
            $this->status("<info>☕ Building Magento %s.</info> <comment>(This shouldn't take more than 5 minutes on a modern system.)</comment>", [$this->getVersion()]);
        }

        $this->installMagento();

        $this->inDocker(
            'web',
            'chown -R www-data:www-data /var/www/html/'
        );
        $this->dockerComposeUp(true);

        $this->status(
            Env::extrapolateEnv(
                '<info>✅ Magento '.$this->getVersion().' is now up & running at '.
                'http://$(M2D_MAGE_WEB_DOMAIN):$(M2D_WEB_PORT)/admin (User: '.
                '$(M2D_MAGE_ADMIN_USER), password: $(M2D_MAGE_ADMIN_PASS))</info>'
            )
        );
    }

    /**
     * Check, whether the local Docker system supports BuildKit.
     *
     * @return bool
     */
    protected function dockerSupportsBuildKit(): bool
    {
        if ($this->_dockerSupportsBuildKit === null) {
            try {
                $dockerVersionExitCode = $this->exec(
                    ['docker', 'version', '--format', '{{.Server.Version}}'],
                    [],
                    $dockerVersion,
                    false
                );

                if ($dockerVersionExitCode !== 0) {
                    $this->_dockerSupportsBuildKit = false;
                } else {
                    if (version_compare($dockerVersion, '18.09', '>=')) {
                        $this->_dockerSupportsBuildKit = true;
                    } else {
                        $this->_dockerSupportsBuildKit = false;
                    }
                }
            } catch (Exception $e) {
                $this->_dockerSupportsBuildKit = false;
            }
        }

        return $this->_dockerSupportsBuildKit;
    }

    /**
     * Build Docker containers.
     *
     * @throws Exception
     */
    protected function dockerBuild()
    {
        $supportsBuildKit = $this->dockerSupportsBuildKit();

        $env = $supportsBuildKit
            ? ['DOCKER_BUILDKIT' => '1']
            : [];

        $this->status('<info>🐋 Building Docker containers...</info> %s', [$supportsBuildKit ? '(accelerated by BuildKit)' : '']);
        $this->dockerCompose('build', $output, true, false, $env);
    }

    /**
     * Start Magento installation inside the container.
     */
    abstract protected function installMagento(): void;

    /**
     * @throws Exception
     */
    public function stop(): void
    {
        if (!$this->isRunning()) {
            $this->status('<info>⏹️  Magento %s is already stopped.</info>', [$this->getVersion()]);
            return;
        }

        $this->status('<info>⏹️  Stopping Magento %s...</info>', [$this->getVersion()]);
        $this->dockerComposeStop();

        $this->status(
            '<info>✅ Magento '.$this->getVersion().' is now stopped.</info>'
        );
    }

    /**
     * @throws Exception
     */
    public function clear(): void
    {
        $dirs = [$this->getMageSrcDir(), $this->getDbDir()];

        $needsSudo = false;
        foreach ($dirs as $dir) {
            if (!$needsSudo && $this->filesystem->exists($dir)) {
                $currentUser = posix_getuid();
                $dirOwner    = fileowner($dir);
                $needsSudo = ($dirOwner !== false) && ($dirOwner !== $currentUser);

                if ($needsSudo) {
                    $continue = $this->io->confirm(
                        "Clearing Magento source files requires root privileges, because the files are not owned by your current user.\n"
                        . "You will be asked for your root password before the files will be deleted.\n"
                        . "Will you know the root password when it is required?",
                        false
                    );

                    if (!$continue) {
                        return;
                    }
                }
            }
        }

        $this->status('<info>🗑️ Clearing Magento %s...</info>', [$this->getVersion()]);
        $this->stop();

        foreach ($dirs as $dir) {
            try {
                if ($this->filesystem->exists($dir)) {
                    if ($needsSudo) {
                        if ($this->dirIsInsideHome($dir)) {
                            $this->status('<comment>sudo rm -rf %s</comment>', [$dir]);
                            $this->exec(['sudo', 'rm', '-rf', $dir], [], $output, false, true);
                        }
                    } else {
                        $this->filesystem->remove($dir);
                    }
                }
            } catch (Exception $e) {
                $this->status('<info>'.$e->getMessage().'</info>');
            }
        }

        $this->clearContainers();

        $this->status('<info>✅ Magento %s was successfully cleared.</info>', [$this->getVersion()]);
    }

    /**
     * Remove Docker containers associated with this Recipe's Magento version.
     *
     * @throws Exception
     */
    protected function clearContainers(): bool
    {
        $this->status('<info>🗑️ Removing Docker containers for Magento %s...</info>', [$this->getVersion()]);

        $this->exec(
            ['docker', 'ps', '-a', '-f', 'name=m2devbox', '--format', '{{.ID}};{{.Names}}'],
            [],
            $containersOutput,
            false
        );

        if (is_string($containersOutput) && !empty($containersOutput)) {
            $containersOutput = explode("\n", $containersOutput);
            $builtContainers = [];
            foreach ($containersOutput as $line) {
                $fields = explode(';', $line);
                /** @psalm-suppress RedundantCondition */
                if (is_array($fields) && count($fields) === 2) {
                    $builtContainers[$fields[0]] = $fields[1];
                }
            }

            $builtContainers = array_intersect($builtContainers, $this->getExpectedContainers());
            foreach ($builtContainers as $id => $name) {
                if (!empty($id)) {
                    $this->exec(
                        ['docker', 'rm', '-f', '-v', $id],
                        [],
                        $removeOutput
                    );
                }
            }

            return true;
        } else {
            return false;
        }
    }

    public function isRunning(): bool
    {
        $this->exec(['docker', 'ps', '--format={{.Names}}'], [], $runningContainers);
        $runningContainers = explode("\n", $runningContainers);
        $runningContainers = array_filter($runningContainers);

        return array_intersect($this->getExpectedContainers(), $runningContainers) == $this->getExpectedContainers();
    }

    /**
     * Execute a shell command and monitor its output.
     * If the current session is not verbose, a spinner animation will be shown
     * during command run (except if a TTY should be allocated).
     *
     * @param array        $commandLine         Command line to execute (one array element per command argument)
     * @param array        $env                 ENV vars for the process
     * @param string|null &$output              (Optional) Command output
     * @param bool         $showOutputInSpinner Show current output line next to spinner animation
     * @param bool         $allocateTty         Allocate a tty
     * @return int|null    Exit code of the command
     * @throws Exception
     */
    public function exec(array $commandLine, array $env = [], string &$output = null, bool $showOutputInSpinner = true, bool $allocateTty = false): ?int
    {
        $io = $this->io;

        $progress = null;

        if ($io && $io->isVerbose()) {
            $__env = Arrays::keyValue2ValuePair($env);

            $io->writeln(
                '<comment>[executing]</comment> '
                .Arrays::implodeValuePairs('=', ' ', $__env).' '
                .implode(' ', $commandLine)
            );
        } elseif (!$allocateTty) {
            // if we are not verbose and should not allocate a TTY, show a spinning animation
            $progress = new Progress((new Snake())->setColorType(Colors::COLOR_TYPE_ANSI256));
            $progress->start(-1);
        }

        // command progress callback. fired on every output line of the process.
        $callback = function ($type, $buffer) use ($io, $progress, $showOutputInSpinner) {
            if ($io && $io->isVerbose()) {
                $io->writeln("<info>\t" . strtoupper($type) . '</info> > ' . trim($buffer));
            } else if ($progress !== null && $showOutputInSpinner) {
                $progress->setStatusMessage($buffer);
            }
        };

        // set process timeout only if we don't allocate a tty
        $timeout = $allocateTty ? null : 3600;

        $p = new Process($commandLine);
        $p
            ->setEnv($env)
            ->setTty($allocateTty)
            ->setTimeout($timeout)
            ->setIdleTimeout($timeout)
            ->start($callback)
        ;

        if ($io && !$io->isVerbose() && $progress !== null) {
            while ($p->isRunning()) {
                $progress->advance();
            }
        }

        if ($io && !$io->isVerbose() && $progress !== null) {
            $progress->finish(ThemeInterface::FINISH_TYPE_CLEAR);
        } else {
            $p->wait($callback);
        }

        if (!$allocateTty && !$p->isSuccessful()) {
            throw new ProcessFailedException($p);
        }

        $output = $p->getOutput();

        return $p->getExitCode();
    }

    /**
     * Run "docker-compose up -d" to start all needed containers.
     *
     * @throws Exception
     */
    protected function dockerComposeUp(bool $detach = false)
    {
        $this->dockerCompose($detach ? ['up', '-d'] : 'up');
    }

    /**
     * Run "docker-compose stop" to stop all needed containers.
     *
     * @throws Exception
     */
    protected function dockerComposeStop()
    {
        $this->dockerCompose('stop');
    }

    public function dockerCompose($arguments, ?string &$output = null, bool $showOutputInSpinner = true, bool $allocateTty = false, array $env = []): ?int
    {
        // shell command to execute
        $commandLine = ['docker-compose'];

        // add Docker Compose YML files via -f
        $composeFiles = $this->getDockerComposeFiles();
        foreach ($composeFiles as $file) {
            array_push($commandLine, '-f', $file);
        }

        // add Docker Compose arguments to shell command line
        if (is_string($arguments)) {
            $arguments = [$arguments];
        }
        array_push($commandLine, ...$arguments);

        // set env vars for Docker Compose yml
        $__env = array_merge(Config::get('default_env'), $env);
        $__env = array_merge($__env, $_ENV);
        $__env = array_merge($__env, [
            '_M2D_DOCKER_PHP_IMG_VERSION' => $this->getPhpImageVersion(),
            '_M2D_MAGE_SHORT_VERSION'     => $this->getShortVersion(),
            '_M2D_DB_DIR'                 => $this->getDbDir(),
            '_M2D_MAGE_SRC_DIR'           => $this->getMageSrcDir(),
            '_M2D_APP_CODE_DIR'           => $this->getAppCodeDir(),
            '_M2D_COMPOSER_CACHE_DIR'     => Config::getComposerHome(),
            '_M2D_COMPOSER_AUTH_FILE'     => Config::getComposerAuth(),
        ]);

        if ($this->getElasticVersion() !== null) {
            $__env['_M2D_DOCKER_ELASTIC_VERSION'] = $this->getElasticVersion();
        }

        if ($this->io->isVerbose()) {
            $envRows = $__env;
            $envRows = Arrays::keyValue2ValuePair($envRows);

            $this->io->table(
                ['Environment var', 'Value'],
                $envRows
            );
        }

        // execute command line
        return $this->exec($commandLine, $__env, $output, $showOutputInSpinner, $allocateTty);
    }

    /**
     * Run a command inside a Docker container.
     *
     * @param string      $service        Compose service name
     * @param string      $command        Command to run
     * @param string|null $waitForService Wait for a service to become healthy. This only works for services which
     *                                    expose a health status!
     * @throws Exception
     */
    protected function inDocker(string $service, string $command, ?string $waitForService = null)
    {
        $wasRunning = $this->isRunning();
        if (!$wasRunning) {
            $this->dockerComposeUp(true);
        }

        $commands = [
            'exec',
            '-T',
            $service,
            '/bin/bash',
            '-c',
            $command
        ];

        if ($waitForService) {
            $healthy = $this->waitForServiceToBecomeHealthy($waitForService);
            if (!$healthy) {
                throw new Exception('Service "'.$waitForService.'" is not healthy. Cannot run command.');
            }
        }

        $this->dockerCompose($commands);

        if (!$wasRunning) {
            $this->dockerComposeStop();
        }
    }

    /**
     * Get the Magento version-specific database directory on the host system.
     *
     * @return string
     * @throws Exception
     */
    protected function getDbDir(): string
    {
        return Config::getCacheDir().'/db/'.$this->getVersion().'/';
    }

    /**
     * Get the Magento version-specific installation source directory on the host system.
     *
     * @return string
     * @throws Exception
     */
    public function getMageSrcDir(): string
    {
        return Config::getCacheDir().'/mage_src/'.$this->getVersion().'/';
    }

    /**
     * Get the local app/code/ directory on the host system (that will be mounted into the container)..
     *
     * @return string
     */
    protected function getAppCodeDir(): string
    {
        $envAppCodeDir = $_ENV['M2D_APP_CODE'];
        // @todo: exists() before readlink() doesn't make sense
        if ($envAppCodeDir && $this->filesystem->exists($envAppCodeDir)) {
            $return = $this->filesystem->readlink($envAppCodeDir, true);
            if (!empty($return)) {
                return $return;
            }
        }

        // fall back to this hard coded directory under the current working dir.
        return getcwd().'/app_code/';
    }

    /**
     * Create the Magento version-specific installation source directory on the host system.
     *
     * @throws Exception
     */
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
     * @throws Exception
     */
    protected function getDockerFilename(string $file): string
    {
        /** @psalm-suppress UndefinedConstant */
        return Config::getDockerConfigDir().'/'.ltrim($file, '/');
    }

    /**
     * Returns a full path to the specified file name in the current recipe version's Magento source directory.
     *
     * @param string $file
     * @return string
     * @throws Exception
     */
    protected function getMageFilename(string $file): string
    {
        return $this->getMageSrcDir().ltrim($file, '/');
    }

    /**
     * Check, whether a file exists inside the version-specific Magento source directory.
     *
     * @param string $file
     * @return bool
     * @throws Exception
     */
    protected function mageFileExists(string $file): bool
    {
        return file_exists($this->getMageFilename($file));
    }

    /**
     * Check, whether a directory exists inside the version-specific Magento source directory.
     *
     * @param string $dir
     * @return bool
     * @throws Exception
     */
    protected function mageDirExists(string $dir): bool
    {
        return is_dir($this->getMageFilename($dir));
    }

    /**
     * Output a new status line. Use this for giving context about the progress
     * of the current operation.
     *
     * @param string|null $message Status message. Supports {@link sprintf()} format strings
     * @param array|null  $args    Optional {@link sprintf()} arguments
     */
    protected function status(?string $message, ?array $args = null)
    {
        // only output something if the current SymfonyStyle is set
        if ($message !== null && $this->io) {
            if (!empty($args)) {
                $message = vsprintf($message, $args);
            }

            $this->io->writeln($message);
        }
    }

    /**
     * Check, if directory $dir is inside the current user's home directory.
     *
     * @param string $dir
     * @return bool
     */
    protected function dirIsInsideHome(string $dir): bool
    {
        if ($dir === '/') {
            return false;
        }

        $dir = realpath($dir);
        $home = realpath($_SERVER['HOME'] ?? '~');

        if (strlen($dir) > 1 && strlen($home) > 1) {
            return str_starts_with($dir, $home);
        }

        return false;
    }

    /**
     * Get service's container health status
     *
     * @param string $serviceName
     * @return string
     */
    protected function getHealthiness(string $serviceName): string {
        $p = new Process(['docker', 'inspect', '--format', '"{{.State.Health.Status}}"', 'm2devbox-'.$this->getShortVersion().'-'.$serviceName]);
        $p
            ->setTty(false)
            ->setTimeout(10)
            ->setIdleTimeout(10)
            ->run();

        return trim($p->getOutput());
    }

    /**
     * Check the container health status of the given service and wait for it to become healthy.
     * This method is blocking and returns true when the container becomes healthy.
     * It returns false when the container becomes unhealthy.
     *
     * @param string $serviceName
     * @param int    $timeout       Timeout in seconds
     * @return bool                 Healthiness status
     */
    protected function waitForServiceToBecomeHealthy(string $serviceName, int $timeout = 300): bool
    {
        $spinner = new Progress((new Snake())->setColorType(Colors::COLOR_TYPE_ANSI256));
        $spinner->start(-1);
        $spinner->setStatusMessage('Waiting for '.$serviceName.' service to become ready...');

        $timeout = microtime(true) + $timeout;

        $status = $this->getHealthiness($serviceName);

        while ($status !== '"healthy"') {
            $spinner->advance();

            if (($status === '"unhealthy"') || (microtime(true) > $timeout)) {
                $spinner->finish(ThemeInterface::FINISH_TYPE_CLEAR);
                return false;
            }

            sleep(1);
            $status = $this->getHealthiness($serviceName);
        }

        $spinner->finish(ThemeInterface::FINISH_TYPE_CLEAR);
        return true;
    }
}
