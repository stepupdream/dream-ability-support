<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Cache\Memoization;

use StepUpDream\DreamAbilitySupport\Cache\Memoization\Memoization;
use StepUpDream\DreamAbilitySupport\Test\TestCase;

class MemoizationTest extends TestCase
{
    use Memoization;

    /**
     * @var int Closure run count.
     */
    protected int $closureRunCount = 0;

    /**
     * @test
     * @dataProvider provideMemoizationTestParams
     */
    public function memoizationTest($mainKey, $subKey): void
    {
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
        self::assertSame($this->closureRunCount, 1);
        self::assertSame($result, 'test');
    }

    /**
     * @test
     */
    public function flushMemoizationTest(): void
    {
        $this->memoizationValues['mainKey'] = ['id' => 1];
        $this->flushMemoization();
        self::assertSame($this->memoizationValues, []);

        $this->memoizationValues['mainKey'] = ['id' => 1];
        $this->memoizationValues['mainKey2'] = ['id' => 2];
        $this->flushMemoization('mainKey');
        self::assertSame($this->memoizationValues, ['mainKey2' => ['id' => 2]]);

        $this->memoizationValues = [];
        $this->memoizationValues['mainKey']['subKey1'] = ['id' => 1];
        $this->memoizationValues['mainKey']['subKey2'] = ['id' => 2];
        $this->flushMemoization('mainKey', 'subKey1');
        self::assertSame($this->memoizationValues, ['mainKey' => ['subKey2' => ['id' => 2]]]);
    }

    /**
     * @return mixed[]
     */
    public function provideMemoizationTestParams(): array
    {
        return [
            [__FUNCTION__, null],
            [__FUNCTION__, 1],
            [__FUNCTION__, 'sbuKey'],
        ];
    }
}
