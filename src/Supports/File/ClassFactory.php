<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\File;

use LogicException;

/**
 * ClassFactory class.
 */
class ClassFactory
{
    /**
     * @var array<string, object>
     */
    protected static array $madeInstance;

    protected static string $vendorDirectory;

    /**
     * @var array<string, string>
     */
    protected static array $autoloadClassmap;

    /**
     * @var array<string, string>
     */
    protected static array $autoloadClassmapInversion;

    /**
     * Create a class instance by class map key.
     */
    public static function make(string $classMapKey, string $cwd = '.'): object
    {
        // Setup autoload data like $autoloadClassmap and $vendorDirectory
        static::setupAutoload($cwd);

        // Check if the class exists in to autoload classmap
        if (!isset(static::$autoloadClassmap[$classMapKey])) {
            throw new LogicException(
                sprintf(
                    'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (%s)',
                    $classMapKey,
                ),
            );
        }

        // Resolve and return the instance
        return static::resolveInstance($classMapKey, $classMapKey);
    }

    /**
     * Create a class instance by the file path.
     */
    public static function makeByPath(string $filePath, string $cwd = '.'): object
    {
        // Setup autoload data like $autoloadClassmap and $vendorDirectory
        static::setupAutoload($cwd);

        // Generate the inversion map (path -> class map key) if not already set.
        if (!isset(static::$autoloadClassmapInversion)) {
            static::$autoloadClassmapInversion = array_flip(static::$autoloadClassmap);
        }

        // Check if the path exists in the autoload classmap inversion.
        if (!isset(static::$autoloadClassmapInversion[$filePath])) {
            throw new LogicException(
                'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue.',
            );
        }

        // Retrieve the class map key from the path
        $classMapKey = static::$autoloadClassmapInversion[$filePath];

        // Resolve and return the instance
        return static::resolveInstance($filePath, $classMapKey);
    }

    /**
     * Resolve an instance from the class map key and save it into the instance cache.
     */
    protected static function resolveInstance(string $key, string $classMapKey): object
    {
        static::$madeInstance[$key] ??= new $classMapKey();

        /** @phpstan-ignore-next-line */
        return static::$madeInstance[$key];
    }

    /**
     * Setup autoload classmap and related data if not already initialized.
     */
    protected static function setupAutoload(string $cwd): void
    {
        if (!isset(static::$vendorDirectory)) {
            static::$vendorDirectory = $cwd . '/vendor';
        }

        if (!isset(static::$autoloadClassmap)) {
            /**
             * Load the autoload classmap as an array using `require`.
             *
             * @var array<string, string> $classmap
             * @SuppressWarnings("php:S2003")
             * @SuppressWarnings("php:S4833")
             * @noinspection UsingInclusionReturnValueInspection
             */
            $classmap = require static::$vendorDirectory . '/composer/autoload_classmap.php';
            static::$autoloadClassmap = $classmap;
        }
    }
}
