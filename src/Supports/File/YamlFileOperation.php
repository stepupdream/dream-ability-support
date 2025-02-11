<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\File;

use LogicException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileOperation class.
 */
class YamlFileOperation extends FileOperation
{
    /**
     * First element key: directory path.
     * Second element key: file path.
     *
     * @var array[][]
     */
    protected array $yamlCache = [];

    /**
     * Read yaml files.
     */
    public function readByFileName(string $directoryPath, string $findFileName): array
    {
        $yamlFiles = $this->readByDirectoryPath($directoryPath);
        foreach ($yamlFiles as $fileName => $yamlFile) {
            if (basename($fileName, '.yml') === $findFileName) {
                return $yamlFile;
            }
        }

        return [];
    }

    /**
     * Read yaml files.
     *
     * @param string[] $exceptFileNames
     * @return array[]
     */
    public function readByDirectoryPath(string $directoryPath, array $exceptFileNames = []): array
    {
        if (!$this->isDirectory($directoryPath)) {
            throw new LogicException($directoryPath . ': read path must be a directory');
        }

        if (empty($this->yamlCache[$directoryPath])) {
            $filePaths = $this->getAllFilePath($directoryPath);
            $yamlFiles = $this->parseAllYaml($filePaths);
            $this->yamlCache[$directoryPath] = $yamlFiles;
        } else {
            $yamlFiles = $this->yamlCache[$directoryPath];
        }

        // Exclude from creation
        if ($exceptFileNames !== [] && $yamlFiles !== []) {
            $yamlFiles = collect($yamlFiles)->filter(fn ($_, $key) => !in_array(basename($key, '.yml'), $exceptFileNames))->all();
        }

        return $yamlFiles;
    }

    /**
     * Determine if the given path is a directory.
     *
     * @see \Illuminate\Filesystem\Filesystem::isDirectory
     */
    protected function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Recursively get a list of file paths from a directory.
     *
     * @return string[]
     */
    protected function getAllFilePath(string $directoryPath): array
    {
        $filePaths = [];

        if (!$this->isDirectory($directoryPath)) {
            throw new LogicException('Not a Directory');
        }

        $files = $this->allFiles($directoryPath);
        foreach ($files as $file) {
            $realPath = (string)$file->getRealPath();
            $filePaths[$realPath] = $realPath;
        }

        return $filePaths;
    }

    /**
     * Parse all definition Yaml files.
     *
     * @param string[] $filePaths
     * @return array[]
     */
    protected function parseAllYaml(array $filePaths): array
    {
        $yamlParseTexts = [];

        foreach ($filePaths as $filePath) {
            $yamlParseTexts[$filePath] = $this->parseYaml($filePath);
        }

        return $yamlParseTexts;
    }

    /**
     * Parses a YAML file and returns its contents as an associative array.
     */
    protected function parseYaml(string $filePath): array
    {
        $extension = $this->extension($filePath);

        if ($extension !== 'yml') {
            throw new LogicException('Could not parse because it is not Yaml data filePath: ' . $filePath);
        }

        $contents = file_get_contents($filePath);
        if (!$contents) {
            throw new LogicException("Didn't get the file :" . $filePath);
        }

        $yaml = Yaml::parse($contents);
        if (!is_array($yaml) || !$this->isMultidimensional($yaml)) {
            throw new LogicException('YAML file description is not in array format: ' . $filePath);
        }

        if (count($yaml) !== 1) {
            throw new LogicException('YAML data must be one data per a file filePath: ' . $filePath);
        }

        // Rule that there is always one data in YAML data
        $resetValue = reset($yaml);
        if (!is_array($resetValue)) {
            throw new LogicException('YAML data structure is invalid, expected array: ' . $filePath);
        }

        return $resetValue;
    }

    /**
     * Extract the file extension from a file path.
     *
     * @see \Illuminate\Filesystem\Filesystem::extension
     */
    protected function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Whether it is a multidimensional array.
     */
    protected function isMultidimensional(array $array): bool
    {
        return count($array) !== count($array, 1);
    }
}
