<?php

namespace Devbox\Service;

use Devbox\RecipeInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class RecipeLoader
{
    /**
     * @param string            $version
     * @param SymfonyStyle|null $io
     * @return RecipeInterface
     * @throws Exception
     */
    public static function get(string $version, ?SymfonyStyle $io = null): RecipeInterface
    {
        if (!in_array($version, Config::get('supported_versions'))) {
            throw new Exception('Version not supported!');
        }

        $recipes = Config::getRecipes();
        /** @var RecipeInterface $recipe */
        $recipe = new $recipes[$version]();
        $recipe->setIo($io);

        return $recipe;
    }

    /**
     * @param SymfonyStyle|null $io
     * @return RecipeInterface[]
     * @throws Exception
     */
    public static function getAll(?SymfonyStyle $io = null): array
    {
        $recipes = Config::getRecipes();
        $result = [];

        foreach ($recipes as $recipe) {
            /** @var RecipeInterface $recipe */
            $recipe = new $recipe();
            $recipe->setIo($io);
            $result[] = $recipe;
        }

        return $result;
    }

    /**
     * @param SymfonyStyle|null $io
     * @return RecipeInterface[]
     * @throws Exception
     */
    public static function getRunning(?SymfonyStyle $io = null): array
    {
        $recipes = Config::getRecipes();

        $result = [];

        foreach ($recipes as $recipe) {
            /** @var RecipeInterface $recipe */
            $recipe = new $recipe();
            if ($recipe->isRunning()) {
                $recipe->setIo($io);
                $result[$recipe->getVersion()] = $recipe;
            }
        }

        return $result;
    }
}
