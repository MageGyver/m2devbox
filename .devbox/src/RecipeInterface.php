<?php

namespace Devbox;

use Symfony\Component\Console\Style\SymfonyStyle;

interface RecipeInterface
{
    public static function getVersion(): string;
    public function setIo(?SymfonyStyle $io): self;
    public function start();
    public function stop();
    public function clear();
    public function isBuilt(): bool;
    public function isRunning(): bool;
}
