<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\File;

use LogicException;

class ClassFactory
{
    /**
     * @var object[]
     */
    protected static array $madeInstance;

    protected static string $vendorDirectory;

    /**
     * @var array<string, string> Autoload class mapを型安全に保持
     */
    protected static array $autoloadClassmap;

    /**
     * @var array<string, string> Autoload class map inversionを型安全に保持
     */
    protected static array $autoloadClassmapInversion;

    /**
     * Create a class.
     */
    public static function make(string $classMapKey, string $cwd = '.'): object
    {
        if (isset(static::$madeInstance[$classMapKey])) {
            return static::$madeInstance[$classMapKey];
        }

        if (! isset(static::$vendorDirectory)) {
            static::$vendorDirectory = $cwd.'/vendor';
        }

        if (! isset(static::$autoloadClassmap)) {
            /**
             * I intentionally use require because I want to read the value of autoload_classmap as an array.
             * @var array<string, string> $classmap
             * @SuppressWarnings("php:S4833")
             */
            $classmap = require static::$vendorDirectory.'/composer/autoload_classmap.php';
            static::$autoloadClassmap = $classmap;
        }

        if (! isset(static::$autoloadClassmap[$classMapKey])) {
            throw new LogicException(sprintf(
                'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (%s)',
                $classMapKey));
        }

        static::$madeInstance[$classMapKey] = new $classMapKey;

        return static::$madeInstance[$classMapKey];
    }

    /**
     * Create a class.
     */
    public static function makeByPath(string $filePath, string $cwd = '.'): object
    {
        if (isset(static::$madeInstance[$filePath])) {
            return static::$madeInstance[$filePath];
        }

        if (! isset(static::$vendorDirectory)) {
            static::$vendorDirectory = $cwd.'/vendor';
        }

        if (! isset(static::$autoloadClassmap)) {
            /**
             * I intentionally use require because I want to read the value of autoload_classmap as an array.
             * @var array<string, string> $classmap
             * @SuppressWarnings("php:S4833")
             */
            $classmap = require static::$vendorDirectory.'/composer/autoload_classmap.php';
            static::$autoloadClassmap = $classmap;
        }

        if (! isset(static::$autoloadClassmapInversion)) {
            static::$autoloadClassmapInversion = array_flip(static::$autoloadClassmap);
        }

        if (! isset(static::$autoloadClassmapInversion[$filePath])) {
            throw new LogicException('It does not exist in the autoload class map.
             Run composer dump-autoload to resolve the issue.');
        }

        $classMapKey = static::$autoloadClassmapInversion[$filePath];

        static::$madeInstance[$filePath] = new $classMapKey;

        return static::$madeInstance[$filePath];
    }
}
