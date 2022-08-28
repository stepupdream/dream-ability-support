<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\File;

use LogicException;

class ClassFactory
{
    /**
     * @var array
     */
    protected static array $madeInstance;

    /**
     * @var string
     */
    protected static string $vendorDirectory;

    /**
     * @var array
     */
    protected static array $autoloadClassmapData;

    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $classMapKey
     * @param  string  $cwd
     * @return Object
     */
    public static function make(string $classMapKey, string $cwd = '.'): object
    {
        if (isset(static::$madeInstance[$classMapKey])) {
            return static::$madeInstance[$classMapKey];
        }

        if (! isset(static::$vendorDirectory)) {
            static::$vendorDirectory = $cwd.'/vendor';
        }

        if (! isset(static::$autoloadClassmapData)) {
            static::$autoloadClassmapData = require(static::$vendorDirectory.'/composer/autoload_classmap.php');
        }

        if (! isset(static::$autoloadClassmapData[$classMapKey])) {
            throw new LogicException(sprintf(
                'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (%s)',
                $classMapKey
            ));
        }

        static::$madeInstance[$classMapKey] = new $classMapKey();

        return static::$madeInstance[$classMapKey];
    }
}
