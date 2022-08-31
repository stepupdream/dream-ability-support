<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Cache\Memoization;

use Closure;

trait Memoization
{
    /**
     * @var mixed[]|mixed[][]
     */
    protected array $memoizationValues = [];

    /**
     * Get an item from the local cache, or execute the given Closure and store the result.
     *
     * @param  string  $mainKey
     * @param  int|string|\Closure  $subKey
     * @param  \Closure|null  $callback
     * @return mixed
     */
    public function memoization(string $mainKey, int|string|Closure $subKey, ?Closure $callback = null): mixed
    {
        if ($subKey instanceof Closure) {
            $callback = $subKey;
            $subKey = '*';
        }

        if (isset($this->memoizationValues[$mainKey][$subKey])) {
            return $this->memoizationValues[$mainKey][$subKey];
        }

        $this->memoizationValues[$mainKey][$subKey] = $callback();

        return $this->memoizationValues[$mainKey][$subKey];
    }

    /**
     * Clear the data.
     *
     * @param  string|null  $mainKey
     * @param  int|string|null  $subKey
     */
    public function flushMemoization(string $mainKey = null, int|string $subKey = null): void
    {
        if ($mainKey === null && $subKey === null) {
            $this->memoizationValues = [];

            return;
        }

        if ($mainKey !== null && $subKey !== null) {
            unset($this->memoizationValues[$mainKey][$subKey]);

            return;
        }

        unset($this->memoizationValues[$mainKey]);
    }
}
