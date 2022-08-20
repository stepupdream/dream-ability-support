<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\File;

use ErrorException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use LogicException;
use Symfony\Component\Finder\Finder;

class FileOperation
{
    /**
     * Create the same file as the first argument at the position specified by the second argument.
     *
     * @param  string  $content
     * @param  string  $filePath
     * @param  bool  $isOverwrite
     * @return bool
     */
    public function createFile(string $content, string $filePath, bool $isOverwrite = false): bool
    {
        $dirPath = dirname($filePath);

        if (! is_dir($dirPath)) {
            $this->makeDirectory($dirPath, 0777, true);
        }

        if (! file_exists($filePath)) {
            $this->put($filePath, $content);

            return true;
        }

        if ($isOverwrite) {
            // Hack:
            // An error occurred when overwriting, so always delete → create
            $this->delete($filePath);
            $this->put($filePath, $content);

            return true;
        }

        return false;
    }

    /**
     * Create git keep file.
     */
    public function createGitKeep(string $directoryPath): void
    {
        if (! is_dir($directoryPath)) {
            $this->createFile('gitkeep', $directoryPath.'/.gitkeep');
        }
    }

    /**
     * Check if it is the same as the file that already exists.
     *
     * @param  string  $content
     * @param  string  $targetDirectoryPath
     * @param  string  $fileName
     * @return bool
     */
    public function shouldCreate(string $content, string $targetDirectoryPath, string $fileName): bool
    {
        if (! is_dir($targetDirectoryPath)) {
            return true;
        }

        $allFiles = $this->allFiles($targetDirectoryPath, true);
        foreach ($allFiles as $allFile) {
            if ($allFile->getFilename() === $fileName) {
                return file_get_contents($allFile->getRealPath()) !== $content;
            }
        }

        return true;
    }

    /**
     * Get all the files from the given directory (recursive).
     *
     * @param  string  $directory
     * @param  bool  $hidden
     * @return \Symfony\Component\Finder\SplFileInfo[]
     * @see \Illuminate\Filesystem\Filesystem::allFiles
     */
    public function allFiles(string $directory, bool $hidden = false): array
    {
        return iterator_to_array(
            Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory)->sortByName(),
            false
        );
    }

    /**
     * Create a directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return void
     * @see \Illuminate\Filesystem\Filesystem::makeDirectory
     */
    private function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): void
    {
        $result = mkdir($path, $mode, $recursive);

        if (! $result) {
            throw new LogicException($path.': Failed to make directory');
        }
    }

    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @see \Illuminate\Filesystem\Filesystem::put
     */
    private function put(string $path, string $contents): void
    {
        $result = file_put_contents($path, $contents);
        if (! $result) {
            throw new LogicException($path.': Failed to create');
        }
    }

    /**
     * Delete the file at a given path.
     *
     * @param  string|string[]  $paths
     * @see \Illuminate\Filesystem\Filesystem::delete
     */
    private function delete(mixed $paths): void
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (! @unlink($path)) {
                    $success = false;
                }
            } catch (ErrorException) {
                $success = false;
            }

            if (! $success) {
                throw new LogicException($path.': Failed to delete');
            }
        }
    }

    /**
     * Add Tab Space.
     *
     * @param  int  $tabCount
     * @return string
     */
    public function addTabSpace(int $tabCount = 1): string
    {
        $result = '';

        for ($i = 1; $i <= $tabCount; $i++) {
            $result .= '    ';
        }

        return $result;
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @return string
     */
    public function get(string $path): string
    {
        if (is_file($path)) {
            $contents = file_get_contents($path);

            if (! $contents) {
                throw new LogicException('Failed to get the file. : '.$path);
            }

            return $contents;
        }

        throw new FileNotFoundException("File does not exist at path {$path}.");
    }
}
