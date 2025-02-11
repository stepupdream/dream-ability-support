<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Cache\Memoization;

use Closure;

/**
 * Traits that implement memoization patterns.
 * Caches calculation results to prevent recalculations for the same input.
 */
trait Memoization
{
    /**
     * @var array|array[]
     */
    protected array $memoizationValues = [];

    /**
     * Get an item from the local cache, or execute the given Closure and store the result.
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
     */
    public function flushMemoization(?string $mainKey = null, int|string|null $subKey = null): void
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
