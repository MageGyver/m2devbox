<?php

namespace Devbox\Service;

class State
{
    protected static ?array $state = null;

    protected static function getStateFileName(): string
    {
        /** @psalm-suppress UndefinedConstant */
        return DB_ROOT.'/state.json';
    }

    public static function load()
    {
        if (static::$state === null) {
            $stateFile = static::getStateFileName();

            if (!file_exists($stateFile)) {
                file_put_contents($stateFile, '{}');
                static::$state = [];
                return;
            }

            static::$state = json_decode(file_get_contents($stateFile), true);
        }
    }

    public static function save()
    {
        if (static::$state) {
            $stateFile = static::getStateFileName();
            file_put_contents($stateFile, json_encode(static::$state, JSON_PRETTY_PRINT));
        }
    }

    public static function get(string $key)
    {
        self::load();

        return array_key_exists($key, self::$state)
            ? self::$state[$key]
            : null;
    }

    public static function set(string $key, $value)
    {
        self::load();
        self::$state[$key] = $value;
        self::save();
    }
}
