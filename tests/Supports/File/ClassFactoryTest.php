<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Supports\File;

use PHPUnit\TextUI\Help;
use StepUpDream\DreamAbilitySupport\Supports\File\ClassFactory;

it('creates a class instance using ClassFactory::make', function () {
    $helpClass = new Help;
    $newClass = ClassFactory::make('PHPUnit\\TextUI\\Help', realpath(__DIR__.'/../../..'));

    expect($newClass)->toEqual($helpClass);
});

it('creates a class instance using ClassFactory::makeByPath', function () {
    $helpClass = new Help;
    $newClass = ClassFactory::makeByPath(
        realpath(__DIR__.'/../../../vendor/phpunit/phpunit/src/TextUI/Help.php'),
        realpath(__DIR__.'/../../..'),
    );

    expect($newClass)->toEqual($helpClass);
});

it('throws exception for non-existing class', function () {
    $this->expectExceptionMessage(
        'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (test)',
    );

    ClassFactory::make('test', __DIR__.'/../../..');
});
