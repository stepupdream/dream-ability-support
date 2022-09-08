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

    /**
     * @var string
     */
    protected static string $vendorDirectory;

    /**
     * @var string[]
     */
    protected static array $autoloadClassmap;

    /**
     * @var string[]
     */
    protected static array $autoloadClassmapInversion;

    /**
     * Create a class.
     *
     * @param  string  $classMapKey
     * @param  string  $cwd
     * @return object
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
            static::$autoloadClassmap = require static::$vendorDirectory.'/composer/autoload_classmap.php';
        }

        if (! isset(static::$autoloadClassmap[$classMapKey])) {
            throw new LogicException(sprintf(
                'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (%s)',
                $classMapKey
            ));
        }

        static::$madeInstance[$classMapKey] = new $classMapKey();

        return static::$madeInstance[$classMapKey];
    }

    /**
     * Create a class.
     *
     * @param  string  $filePath
     * @param  string  $cwd
     * @return object
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
            static::$autoloadClassmap = require static::$vendorDirectory.'/composer/autoload_classmap.php';
        }

        if (! isset(static::$autoloadClassmapInversion)) {
            static::$autoloadClassmapInversion = array_flip(static::$autoloadClassmap);
        }

        if (! isset(static::$autoloadClassmapInversion[$filePath])) {
            throw new LogicException(sprintf(
                'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (%s)',
                $filePath
            ));
        }

        $classMapKey = static::$autoloadClassmapInversion[$filePath];

        static::$madeInstance[$filePath] = new $classMapKey();

        return static::$madeInstance[$filePath];
    }
}
