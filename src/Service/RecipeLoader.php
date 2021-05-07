<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace MageGyver\M2devbox\Service;

use MageGyver\M2devbox\RecipeInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class RecipeLoader
{
    protected static $recipesCache = [];

    /**
     * Get the Recipe instance for a given Magento version.
     *
     * @param string            $version
     * @param SymfonyStyle|null $io         Console IO for status messages
     * @return RecipeInterface
     * @throws Exception
     */
    public static function get(string $version, ?SymfonyStyle $io = null): RecipeInterface
    {
        if (!array_key_exists($version, Config::get('supported_versions'))) {
            throw new Exception('Version not supported!');
        }

        if (!array_key_exists($version, self::$recipesCache)) {
            $recipes = Config::getRecipes();
            /** @var RecipeInterface $recipe */
            $recipe = new $recipes[$version]['recipe_class']();
            $recipe->configure($recipes[$version]);

            self::$recipesCache[$version] = $recipe;
        }

        $recipe = self::$recipesCache[$version];
        return $recipe->setIo($io);
    }

    /**
     * Get all Recipe instances.
     *
     * @param SymfonyStyle|null $io
     * @return RecipeInterface[]
     * @throws Exception
     */
    public static function getAll(?SymfonyStyle $io = null): array
    {
        $recipes = Config::getRecipes();

        foreach ($recipes as $version => $versionConfig) {
            self::get($version, $io);
        }

        return self::$recipesCache;
    }

    /**
     * Get the Recipe instances for all running Magento versions.
     *
     * @param SymfonyStyle|null $io
     * @return RecipeInterface[]
     * @throws Exception
     */
    public static function getRunning(?SymfonyStyle $io = null): array
    {
        $result = self::getAll($io);

        return array_filter($result, function(RecipeInterface $recipe) {
            return $recipe->isRunning();
        });
    }

    /**
     * Get the Recipe correspondig to the newest support Magento 2 version.
     *
     * @param SymfonyStyle|null $io
     * @return RecipeInterface
     * @throws Exception
     */
    public static function getNewest(?SymfonyStyle $io = null): RecipeInterface
    {
        $recipes = self::getAll($io);
        uksort($recipes, function ($a, $b) {
            return version_compare($a, $b);
        });

        return end($recipes);
    }
}
