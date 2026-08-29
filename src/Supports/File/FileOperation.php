<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\File;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use LogicException;
use StepUpDream\DreamAbilitySupport\Supports\CodeGeneration\PreservedBlockMerger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * FileOperation class.
 */
class FileOperation
{
    /**
     * Create the same file as the first argument at the position specified by the second argument.
     */
    public function createFile(string $content, string $filePath, bool $isOverwrite = false): bool
    {
        $result = false;
        $dirPath = dirname($filePath);

        if (!is_dir($dirPath)) {
            $this->makeDirectory($dirPath, 0777, true);
        }

        if (file_exists($filePath)) {
            if ($isOverwrite) {
                // Hack:
                // An error occurred when overwriting, so always delete → create
                $this->delete($filePath);
                $this->put($filePath, $content);
                $result = true;
            }
        } else {
            $this->put($filePath, $content);
            $result = true;
        }

        return $result;
    }

    /**
     * Create the file while keeping the preserved blocks of the file that already exists.
     *
     * Unlike createFile(), the file is always regenerated: only the ranges that are surrounded by the markers of
     * PreservedBlockMerger are taken over from the existing file, so hand-written code is not lost.
     *
     * @return bool Whether the file was created or updated.
     */
    public function createFilePreservingBlock(
        string $content,
        string $filePath,
        ?PreservedBlockMerger $preservedBlockMerger = null,
    ): bool {
        if (!file_exists($filePath)) {
            return $this->createFile($content, $filePath);
        }

        $preservedBlockMerger ??= new PreservedBlockMerger();
        $currentContent = $this->get($filePath);
        $mergedContent = $preservedBlockMerger->merge($content, $currentContent);

        if ($mergedContent === $currentContent) {
            return false;
        }

        return $this->createFile($mergedContent, $filePath, true);
    }

    /**
     * Create git keep file.
     */
    public function createGitKeep(string $directoryPath): void
    {
        if (!is_dir($directoryPath)) {
            $this->createFile('gitkeep', $directoryPath . '/.gitkeep');
        }
    }

    /**
     * Check if it is the same as the file that already exists.
     */
    public function isContentDifferent(string $content, string $targetDirectoryPath, string $fileName): bool
    {
        if (!is_dir($targetDirectoryPath)) {
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
     * @return SplFileInfo[]
     *
     * @see \Illuminate\Filesystem\Filesystem::allFiles
     */
    public function allFiles(string $directory, bool $hidden = false): array
    {
        return iterator_to_array(Finder::create()->files()->ignoreDotFiles(!$hidden)->in($directory)->sortByName(), false);
    }

    /**
     * Make a directory.
     *
     * @see \Illuminate\Filesystem\Filesystem::makeDirectory
     */
    private function makeDirectory(string $directoryPath, int $mode = 0755, bool $recursive = false): void
    {
        $result = mkdir($directoryPath, $mode, $recursive);

        if (!$result) {
            throw new LogicException($directoryPath . ': Failed to make directory');
        }
    }

    /**
     * Write the contents of a file.
     *
     * @see \Illuminate\Filesystem\Filesystem::put
     */
    private function put(string $path, string $contents): void
    {
        $result = file_put_contents($path, $contents);
        if ($result === false) {
            throw new LogicException($path . ': Failed to create');
        }
    }

    /**
     * Delete the file at a given path.
     *
     * @param string|string[] $paths
     *
     * @see \Illuminate\Filesystem\Filesystem::delete
     */
    private function delete(array|string $paths): void
    {
        /** @var string[] $paths */
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            if (!unlink($path)) {
                $success = false;
            }

            if (!$success) {
                throw new LogicException($path . ': Failed to delete');
            }
        }
    }

    /**
     * Add Tab Space.
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
     */
    public function get(string $path): string
    {
        if (is_file($path)) {
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new LogicException("Failed to get the file. : $path.");
            }

            return $contents;
        }

        throw new FileNotFoundException("File does not exist at path $path.");
    }

    /**
     * Whether a file with the same filename exists or not.
     */
    public function isSameFileNameExist(string $directory): bool
    {
        $names = [];
        $files = $this->allFiles($directory);
        foreach ($files as $file) {
            if (in_array($file->getFilename(), $names, true)) {
                return true;
            }

            $names[] = $file->getFilename();
        }

        return false;
    }
}
