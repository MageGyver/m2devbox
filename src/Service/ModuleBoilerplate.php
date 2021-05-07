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

use DirectoryIterator;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use const MageGyver\M2devbox\DB_SRC;

class ModuleBoilerplate
{
    protected Filesystem $filesystem;

    /**
     * ModuleBoilerplate constructor.
     */
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function isDirEmpty(string $dir): bool
    {
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
        return true;
    }

    /**
     * @param string     $moduleDir    Module project root directory
     * @param bool       $createPhpStormProject
     * @param string[][] $placeholders Array of placeholders to replace in boilerplate file names and contents
     */
    public function createModule(string $moduleDir, bool $createPhpStormProject, array $placeholders = [])
    {
        if ($this->filesystem->exists($moduleDir) && !$this->isDirEmpty($moduleDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" is not empty!', $moduleDir));
        }

        $this->createBoilerplateFiles($moduleDir, $createPhpStormProject);
        $this->replacePlaceholders($moduleDir, $placeholders);
    }

    protected function createBoilerplateFiles(string $targetDirectory, bool $createPhpStormProject): void
    {
        /** @psalm-suppress UndefinedConstant */
        $originDir = DB_SRC.'/etc/module-boilerplate';
        $directoryIterator = new RecursiveDirectoryIterator($originDir, \FilesystemIterator::SKIP_DOTS);

        /**
         * @noinspection PhpParamsInspection
         * @psalm-suppress InvalidArgument
         */
        $filterIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            function (SplFileInfo $current) use ($createPhpStormProject) {
                // skip .idea folder if we don't want to create a PhpStorm project
                return !(!$createPhpStormProject && $current->isDir() && $current->getFilename() === '.idea');
            }
        );

        $iterator = new \RecursiveIteratorIterator(
            $filterIterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $this->filesystem->mirror($originDir, $targetDirectory, $iterator);
    }

    /**
     * Recursively delete empty subfolders form $path
     *
     * @see https://stackoverflow.com/a/1833681/219467
     * @param string $path
     * @return bool
     */
    protected function removeEmptySubFolders(string $path): bool
    {
        $empty = true;
        foreach (glob($path . DIRECTORY_SEPARATOR . "*") as $file) {
            if (is_dir($file)) {
                if (!$this->removeEmptySubFolders($file)) {
                    $empty = false;
                }
            } else {
                $empty = false;
            }
        }
        if ($empty) {
            rmdir($path);
        }

        return $empty;
    }

    protected function replacePlaceholders(string $moduleDir, array $placeholders): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $moduleDir,
                FilesystemIterator::KEY_AS_PATHNAME
                | FilesystemIterator::CURRENT_AS_FILEINFO
                | FilesystemIterator::SKIP_DOTS
            )
        );

        $moduleDirLen = mb_strlen($moduleDir);

        /** @var DirectoryIterator $file */
        foreach ($iterator as $file) {
            // replace placeholders in filename
            $oldFilename = mb_substr($file->getRealPath(), $moduleDirLen);
            $realFilePath = $file->getRealPath();
            $newFilename = self::extrapolatePlaceholders($oldFilename, $placeholders, '___');
            if ($newFilename !== $oldFilename) {
                $realFilePath = $moduleDir . $newFilename;
                $realFileDir = dirname($realFilePath);
                if (!$this->filesystem->exists($realFileDir)) {
                    $this->filesystem->mkdir($realFileDir);
                }

                $this->filesystem->rename($file->getRealPath(), $realFilePath);
            }

            if ($file->isDir()) {
                continue;
            }

            // replace placeholders in file content
            $content = file_get_contents($realFilePath);
            $newContent = self::extrapolatePlaceholders($content, $placeholders, '###');
            if ($newContent !== $content) {
                $this->filesystem->dumpFile($realFilePath, $newContent);
            }
        }

        $this->removeEmptySubFolders($moduleDir);
    }

    public static function extrapolatePlaceholders(string $string, array $placeholders, string $enclosure): string
    {
        if (preg_match('/'.$enclosure.'.*'.$enclosure.'/mU', $string) === 1) {
            $replacements = [];

            array_walk($placeholders, function ($v, $k) use (&$replacements, $enclosure) {
                $replacements[$enclosure . $k . $enclosure] = $v;
            });

            $string = strtr($string, $replacements);
        }

        return $string;
    }
}
