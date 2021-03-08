<?php

namespace Devbox\Service;

use Devbox\RecipeInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class RecipeLoader
{
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

        $recipes = Config::getRecipes();
        /** @var RecipeInterface $recipe */
        $recipe = new $recipes[$version]['recipe_class']();
        $recipe->configure($recipes[$version]);
        $recipe->setIo($io);

        return $recipe;
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
        $result = [];

        foreach ($recipes as $version => $versionConfig) {
            $result[] = self::get($version, $io);
        }

        return $result;
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

        $result = array_filter($result, function(RecipeInterface $recipe) {
            return $recipe->isRunning();
        });

        return $result;
    }
}
