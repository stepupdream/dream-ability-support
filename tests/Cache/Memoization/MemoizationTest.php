<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Cache\Memoization;

use StepUpDream\DreamAbilitySupport\Cache\Memoization\Memoization;
use StepUpDream\DreamAbilitySupport\Test\TestCase;

uses(TestCase::class, Memoization::class)->in(__FILE__);

beforeEach(function () {
    $this->closureRunCount = 0;
});

it('tests memoization', function (string $mainKey, $subKey) {
    $this->closureRunCount = 0;

    for ($i = 0; $i < 1000; $i++) {
        if ($subKey === null) {
            $result = $this->memoization($mainKey, function () {
                $this->closureRunCount++;

                return 'test';
            });
        } else {
            $result = $this->memoization($mainKey, $subKey, function () {
                $this->closureRunCount++;

                return 'test';
            });
        }
    }

    expect($this->closureRunCount)->toBe(1)->and($result)->toBe('test');
})->with('memoization test params');

it('tests flushMemoization', function () {
    $this->memoizationValues['mainKey'] = ['id' => 1];
    $this->flushMemoization();
    expect($this->memoizationValues)->toBe([]);

    $this->memoizationValues['mainKey'] = ['id' => 1];
    $this->memoizationValues['mainKey2'] = ['id' => 2];
    $this->flushMemoization('mainKey');
    expect($this->memoizationValues)->toBe(['mainKey2' => ['id' => 2]]);

    $this->memoizationValues = [];
    $this->memoizationValues['mainKey']['subKey1'] = ['id' => 1];
    $this->memoizationValues['mainKey']['subKey2'] = ['id' => 2];
    $this->flushMemoization('mainKey', 'subKey1');
    expect($this->memoizationValues)->toBe(['mainKey' => ['subKey2' => ['id' => 2]]]);
});

dataset('memoization test params', fn () => [
    [__FUNCTION__, null],
    [__FUNCTION__, 1],
    [__FUNCTION__, 'sbuKey'],
]);
