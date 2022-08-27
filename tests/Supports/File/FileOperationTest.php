<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Supports\File;

use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;
use StepUpDream\DreamAbilitySupport\Test\TestCase;

class FileOperationTest extends TestCase
{
    /**
     * @test
     */
    public function shouldCreate(): void
    {
        $fileOperation = new FileOperation();
        $result = $fileOperation->shouldCreate("abcdef\n", __DIR__.'/Sample', 'sample.txt');
        self::assertEquals(false, $result);
    }

    /**
     * @test
     */
    public function addTabSpace(): void
    {
        $fileOperation = $this->app->make(FileOperation::class);
        $space = $fileOperation->addTabSpace(2);
        $testResult = '        ';

        self::assertSame($space, $testResult);
    }

    /**
     * @test
     */
    public function createFile(): void
    {
        $outputPath = __DIR__.'/YamlTest/Sample/test.yml';
        $outputDirectoryPath = __DIR__.'/YamlTest/Sample';
        $testFilePath = __DIR__.'/YamlTest/sample3.yml';

        $fileOperation = $this->app->make(FileOperation::class);
        $fileOperation->createFile("aaa\n", $outputPath);
        self::assertFileEquals($outputPath, $testFilePath);

        $fileOperation = $this->app->make(FileOperation::class);
        $fileOperation->createFile("aaa\n", $outputPath, true);
        self::assertFileEquals($outputPath, $testFilePath);

        if (is_file($outputPath)) {
            unlink($outputPath);
            rmdir($outputDirectoryPath);
        }
    }

    /**
     * @test
     */
    public function isSameFileNameExist(): void
    {
        $fileOperation = $this->app->make(FileOperation::class);
        $result = $fileOperation->isSameFileNameExist(__DIR__.'/Sample2');

        self::assertTrue($result);
    }
}
