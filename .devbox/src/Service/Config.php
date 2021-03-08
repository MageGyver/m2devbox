<?php

namespace Devbox\Service;

use Devbox\AbstractRecipe;
use Exception;

class Config
{
    protected static ?array $config = null;

    /**
     *  Load the config file.
     *
     * @param string|null $file
     */
    public static function load(?string $file = null)
    {
        if (self::$config === null) {
            if ($file === null) {
                /** @psalm-suppress UndefinedConstant */
                $file = DB_SRC.'/etc/config.php';
            }

            /** @noinspection PhpIncludeInspection */
            self::$config = include $file;
        }
    }

    /**
     *  Load the config from an array.
     *
     * @param array $config
     */
    public static function loadFromArray(array $config)
    {
        if (self::$config === null) {
            self::$config = $config;
        }
    }

    /**
     * Read a config value
     *
     * @param string $key   Config key
     * @return mixed        Config value
     */
    public static function get(string $key)
    {
        self::load();

        if (!array_key_exists($key, self::$config)) {
            return null;
        }

        return self::$config[$key];
    }

    /**
     * Return a map of supported Magento versions and their recipe FQCN.
     * @return array
     */
    public static function getRecipes(): array
    {
        $result = [];

        $versions = static::get('supported_versions');
        foreach ($versions as $version => $versionConfig) {
            $versionConfig['recipe_class'] = '\Devbox\Recipe\\'.$versionConfig['recipe_class'];

            if (class_exists($versionConfig['recipe_class']) && is_subclass_of($versionConfig['recipe_class'], AbstractRecipe::class)) {
                $result[$version] = $versionConfig;
            }
        }

        // sort by version number
        uksort($result, 'version_compare');

        return $result;
    }

    /**
     * Get the system user home directory (i.e. ~).
     *
     * @return string   Home directory (without trailing slash)
     * @throws Exception
     */
    public static function getUserHomeDir(): string
    {
        $homedir = getenv('HOME');
        if ($homedir === false || empty($homedir) || !is_dir($homedir)) {
            $homedir = getenv('USERPROFILE');   // for windows users
            if ($homedir === false || empty($homedir) || !is_dir($homedir)) {
                throw new Exception('Could not determine system user $HOME directory!');
            }
        }

        return $homedir;
    }

    /**
     * Get the system cache directory (i.e. ~/.cache/mage2devbox).
     *
     * @return string   Cache directory (without trailing slash)
     * @throws Exception
     */
    public static function getCacheDir(): string
    {
        $cacheDir = getenv('XDG_CACHE_HOME');
        if ($cacheDir === false || empty($cacheDir) || !is_dir($cacheDir)) {
            $homeDir = self::getUserHomeDir();
            $cacheDir = $homeDir.'/.cache';
        }

        $cacheDir = $cacheDir.'/mage2devbox';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        return $cacheDir;
    }

    /**
     * Get the system config directory (i.e. ~/.config/mage2devbox).
     *
     * @return string   Config directory (without trailing slash)
     * @throws Exception
     * @throws Exception
     */
    public static function getConfigDir(): string
    {
        $configDir = getenv('XDG_CONFIG_HOME');
        if ($configDir === false || empty($configDir) || !is_dir($configDir)) {
            $homeDir = self::getUserHomeDir();
            $configDir = $homeDir.'/.config';
        }

        $configDir = $configDir.'/mage2devbox';

        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }

        return $configDir;
    }

    /**
     * Get the absolute path to the global Composer binary.
     *
     * @return string
     * @throws Exception
     */
    public static function getComposerBinary(): string
    {
        $bin = exec('which composer', $output, $exitcode);

        if ($exitcode > 0) {
            throw new Exception('Global Composer installation seems to be missing!');
        }

        if (!file_exists($bin)) {
            throw new Exception('Composer binary "'.$bin.'" does not exist!');
        }

        return $bin;
    }

    /**
     * Get the absolute path to the global Composer directory.
     *
     * @return string
     * @throws Exception
     */
    public static function getComposerHome(): string
    {
        $bin = self::getComposerBinary();
        $composerDir = exec(escapeshellcmd($bin).' config --global home');

        if (!is_dir($composerDir)) {
            throw new Exception('Composer home directory "'.$composerDir.'" does not exist!');
        }

        if (!is_readable($composerDir)) {
            throw new Exception('Composer home directory "'.$composerDir.'" is not readable!');
        }

        return $composerDir;
    }

    /**
     * Get the absolute path to the global Composer auth file.
     *
     * @return string|null
     * @throws Exception
     */
    public static function getComposerAuth(): ?string
    {
        $composerHome = self::getComposerHome();
        $authFile = $composerHome.'/auth.json';

        if (file_exists($authFile) && is_readable($authFile)) {
            return $authFile;
        }

        return null;
    }
}
