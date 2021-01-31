<?php

namespace Devbox\Service;

use Devbox\AbstractRecipe;

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
}
