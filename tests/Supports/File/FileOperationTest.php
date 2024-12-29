<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Supports\File;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;
use Symfony\Component\Finder\SplFileInfo;

// Mock the filesystem for testing if needed.
beforeEach(function () {
    $this->fileOperation = new FileOperation;
    $this->tempDir = sys_get_temp_dir().'/test-dir-'.uniqid();
    mkdir($this->tempDir, 0777, true);
});

afterEach(function () {
    // Recursive directory deletion function
    $deleteDirectory = function (string $directory) use (&$deleteDirectory) {
        if (! is_dir($directory)) {
            return;
        }

        // Get all items in the directory except "." and ".."
        $items = array_diff(scandir($directory), ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($itemPath)) {
                // If it's a directory, recursively delete its contents
                $deleteDirectory($itemPath);
            } else {
                // If it's a file, delete it
                unlink($itemPath);
            }
        }

        // Remove the current (now empty) directory
        rmdir($directory);
    };

    // Cleanup the temporary directory if it exists
    if (isset($this->tempDir)) {
        $deleteDirectory($this->tempDir);
    }
});

describe('createFile', function () {
    it('creates a new file if it does not exist', function () {
        $filePath = $this->tempDir.'/test.txt';
        $content = 'Hello World';
        $result = $this->fileOperation->createFile($content, $filePath);
        expect($result)->toBeTrue()
            ->and(file_exists($filePath))->toBeTrue()
            ->and(file_get_contents($filePath))->toBe($content);
    });

    it('overwrites an existing file when $isOverwrite is true', function () {
        $filePath = $this->tempDir.'/test.txt';
        $initialContent = 'Initial Content';
        $newContent = 'Updated Content';
        file_put_contents($filePath, $initialContent);

        $result = $this->fileOperation->createFile($newContent, $filePath, true);
        expect($result)->toBeTrue()
            ->and(file_get_contents($filePath))->toBe($newContent);
    });

    it('does not overwrite an existing file when $isOverwrite is false', function () {
        $filePath = $this->tempDir.'/test.txt';
        $initialContent = 'Initial Content';
        $newContent = 'Updated Content';
        file_put_contents($filePath, $initialContent);

        $result = $this->fileOperation->createFile($newContent, $filePath);
        expect($result)->toBeFalse()
            ->and(file_get_contents($filePath))->toBe($initialContent);
    });
});

describe('createGitKeep', function () {
    it('creates a .gitkeep file', function () {
        $directoryPath = $this->tempDir.'/nested-dir';
        $this->fileOperation->createGitKeep($directoryPath);
        $gitKeepPath = $directoryPath.'/.gitkeep';

        expect(file_exists($directoryPath))->toBeTrue()
            ->and(file_exists($gitKeepPath))->toBeTrue()
            ->and(file_get_contents($gitKeepPath))->toBe('gitkeep');
    });

    it('does nothing if the .gitkeep file already exists', function () {
        $directoryPath = $this->tempDir;
        $gitKeepPath = $directoryPath.'/.gitkeep';
        file_put_contents($gitKeepPath, 'existing');

        $this->fileOperation->createGitKeep($directoryPath);
        expect(file_get_contents($gitKeepPath))->toBe('existing');
    });
});

describe('shouldCreate', function () {
    it('returns true if the directory does not exist', function () {
        $targetDirectoryPath = $this->tempDir.'/non-existent-dir';
        $content = 'Test Content';
        $fileName = 'test.txt';

        $result = $this->fileOperation->isContentDifferent($content, $targetDirectoryPath, $fileName);
        expect($result)->toBeTrue();
    });

    it('returns true if the file content is different', function () {
        $targetDirectoryPath = $this->tempDir;
        $fileName = 'test.txt';
        $filePath = $targetDirectoryPath.'/'.$fileName;
        file_put_contents($filePath, 'Different Content');

        $result = $this->fileOperation->isContentDifferent('Updated Content', $targetDirectoryPath, $fileName);
        expect($result)->toBeTrue();
    });

    it('returns false if the file content is the same', function () {
        $targetDirectoryPath = $this->tempDir;
        $fileName = 'test.txt';
        $content = 'Same Content';
        $filePath = $targetDirectoryPath.'/'.$fileName;
        file_put_contents($filePath, $content);

        $result = $this->fileOperation->isContentDifferent($content, $targetDirectoryPath, $fileName);
        expect($result)->toBeFalse();
    });
});

describe('allFiles', function () {
    it('retrieves all files in a directory', function () {
        file_put_contents($this->tempDir.'/file1.txt', 'Content 1');
        file_put_contents($this->tempDir.'/file2.txt', 'Content 2');

        $files = $this->fileOperation->allFiles($this->tempDir);
        expect($files)->toHaveCount(2)
            ->and($files[0])->toBeInstanceOf(SplFileInfo::class);
    });

    it('ignores dotfiles by default', function () {
        file_put_contents($this->tempDir.'/.hidden', 'Hidden Content');
        file_put_contents($this->tempDir.'/visible.txt', 'Visible Content');

        $files = $this->fileOperation->allFiles($this->tempDir);
        expect($files)->toHaveCount(1)
            ->and($files[0]->getFilename())->toBe('visible.txt');
    });
});

describe('isSameFileNameExist', function () {
    it('returns true if duplicate filenames exist', function () {
        file_put_contents($this->tempDir.'/file1.txt', 'Content 1');
        mkdir($this->tempDir.'/nested');
        file_put_contents($this->tempDir.'/nested/file1.txt', 'Content 2');

        $result = $this->fileOperation->isSameFileNameExist($this->tempDir);
        expect($result)->toBeTrue();
    });

    it('returns false if all filenames are unique', function () {
        file_put_contents($this->tempDir.'/file1.txt', 'Content 1');
        file_put_contents($this->tempDir.'/file2.txt', 'Content 2');

        $result = $this->fileOperation->isSameFileNameExist($this->tempDir);
        expect($result)->toBeFalse();
    });
});

describe('addTabSpace', function () {
    it('adds the correct number of tab spaces', function () {
        $result = $this->fileOperation->addTabSpace(3);
        expect($result)->toBe('            ');
    });

    it('returns an empty string for zero tabs', function () {
        $result = $this->fileOperation->addTabSpace(0);
        expect($result)->toBe('');
    });
});

describe('get', function () {
    it('returns the content of an existing file', function () {
        $filePath = $this->tempDir.'/test.txt';
        $content = 'File Content';
        file_put_contents($filePath, $content);

        $result = $this->fileOperation->get($filePath);
        expect($result)->toBe($content);
    });

    it('throws an exception if the file does not exist', function () {
        $this->fileOperation->get($this->tempDir.'/non-existent-file.txt');
    })->throws(FileNotFoundException::class);
});
