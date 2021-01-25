<?php

namespace Devbox\Service;

use Devbox\AbstractRecipe;

class Config
{
    protected static ?array $config = null;

    /**
     *  Load the config file.
     */
    protected static function load()
    {
        if (self::$config === null) {
            self::$config = include DB_SRC.'/etc/config.php';
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

    public static function getRecipes(): array
    {
        $result = [];

        $versions = static::get('supported_versions');
        foreach ($versions as $version) {
            $versionId = str_replace('.', '', $version);
            $className = '\Devbox\Recipe\Mage'.$versionId;

            if (class_exists($className) && is_subclass_of($className, AbstractRecipe::class)) {
                $result[$version] = $className;
            }
        }

        // sort by version number
        uksort($result, 'version_compare');

        return $result;
    }
}
