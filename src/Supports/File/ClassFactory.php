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
     * Convert a value to studly caps case.
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
}
