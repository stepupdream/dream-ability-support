<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Supports\File;

use StepUpDream\DreamAbilitySupport\Supports\File\YamlFileOperation;
use StepUpDream\DreamAbilitySupport\Test\TestCase;

class YamlFileOperationTest extends TestCase
{
    /**
     * @var mixed[]
     */
    protected array $testResult = [
        [
            'database_directory_name' => 'MasterData',
            'domain_group'            => 'Common',
            'columns'                 => [
                [
                    'name'        => 'id',
                    'description' => 'id',
                ],
                [
                    'name'        => 'name',
                    'description' => 'name',
                ],
                [
                    'name'        => 'level',
                    'description' => 'level',
                ],
            ],
        ],
        [
            'database_directory_name' => 'MasterData',
            'domain_group'            => 'Test',
            'columns'                 => [
                [
                    'name'        => 'id',
                    'description' => 'id',
                ],
                [
                    'name'        => 'name',
                    'description' => 'name',
                ],
                [
                    'name'        => 'level',
                    'description' => 'level',
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function readByDirectoryPath(): void
    {
        $yamlFileOperation = $this->app->make(YamlFileOperation::class);
        $textDirectory = __DIR__.'/YamlTest/Yaml';
        $parseAllYaml = $yamlFileOperation->readByDirectoryPath($textDirectory, []);
        $testResult = collect($parseAllYaml)->values()->all();
        self::assertSame($this->testResult, $testResult);

        $parseAllYaml2 = $yamlFileOperation->readByDirectoryPath($textDirectory, ['common']);
        $parseAllYaml2 = collect($parseAllYaml2)->values()->all();
        $yamlFile = collect($this->testResult)->take(2)->values()->all();
        self::assertSame($yamlFile, $parseAllYaml2);
    }

    /**
     * @test
     */
    public function readByFileName(): void
    {
        $textDirectory = __DIR__.'/YamlTest/Yaml';
        $yamlFileOperation = $this->app->make(YamlFileOperation::class);
        $yamlFile = $yamlFileOperation->readByFileName($textDirectory, 'sample');
        $testResult = collect($this->testResult)->first();

        self::assertSame($yamlFile, $testResult);
    }
}
