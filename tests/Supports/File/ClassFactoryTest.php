<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Supports\File;

use PHPUnit\TextUI\Help;
use StepUpDream\DreamAbilitySupport\Supports\File\ClassFactory;
use StepUpDream\DreamAbilitySupport\Test\TestCase;

class ClassFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function make(): void
    {
        $helpClass = new Help();
        $newClass = ClassFactory::make('PHPUnit\\TextUI\\Help', __DIR__.'/../../..');

        static::assertEquals($helpClass, $newClass);
    }

    /**
     * @test
     */
    public function makeException(): void
    {
        $this->expectExceptionMessage(
            'It does not exist in the autoload class map. Run composer dump-autoload to resolve the issue. (aaaa)'
        );

        ClassFactory::make('aaaa', __DIR__.'/../../..');
    }
}
