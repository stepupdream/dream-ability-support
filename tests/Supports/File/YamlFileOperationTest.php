<?php

declare(strict_types=1);

use StepUpDream\DreamAbilitySupport\Supports\File\YamlFileOperation;

beforeEach(function () {
    $this->testResult = [
        [
            'database_directory_name' => 'MasterData',
            'domain_group' => 'Common',
            'columns' => [
                [
                    'name' => 'id',
                    'description' => 'id',
                ],
                [
                    'name' => 'name',
                    'description' => 'name',
                ],
                [
                    'name' => 'level',
                    'description' => 'level',
                ],
            ],
        ],
        [
            'database_directory_name' => 'MasterData',
            'domain_group' => 'Test',
            'columns' => [
                [
                    'name' => 'id',
                    'description' => 'id',
                ],
                [
                    'name' => 'name',
                    'description' => 'name',
                ],
                [
                    'name' => 'level',
                    'description' => 'level',
                ],
            ],
        ],
    ];
});

it('can read data by directory path', function () {
    $yamlFileOperation = app(YamlFileOperation::class);
    $textDirectory = __DIR__.'/YamlTest/Yaml';

    // Read all data without filtering by path
    $parseAllYaml = $yamlFileOperation->readByDirectoryPath($textDirectory);
    $testResult = collect($parseAllYaml)->values()->all();
    expect($testResult)->toBe($this->testResult);

    // Read and filter data based on the specified path
    $parseAllYaml2 = $yamlFileOperation->readByDirectoryPath($textDirectory, ['common']);
    $parseAllYaml2 = collect($parseAllYaml2)->values()->all();
    $yamlFile = collect($this->testResult)->take(2)->values()->all();
    expect($parseAllYaml2)->toBe($yamlFile);
});

it('can read data by file name', function () {
    $textDirectory = __DIR__.'/YamlTest/Yaml';
    $yamlFileOperation = app(YamlFileOperation::class);

    // Read a single YAML file by its filename
    $yamlFile = $yamlFileOperation->readByFileName($textDirectory, 'sample');
    $testResult = collect($this->testResult)->first();

    expect($yamlFile)->toBe($testResult);
});
