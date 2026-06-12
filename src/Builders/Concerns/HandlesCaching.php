<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders\Concerns;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Provides opt-in result caching for builders.
 */
trait HandlesCaching
{
    protected ?int $cacheDuration = null;

    /**
     * Enable caching for this query
     *
     * @param  int|null  $minutes  Cache duration in minutes (null = use config default)
     */
    public function cache(?int $minutes = null): static
    {
        $this->cacheDuration = $minutes ?? config('cbu-currency.cache_duration');

        return $this;
    }

    /**
     * Disable caching for this query
     */
    public function withoutCache(): static
    {
        $this->cacheDuration = null;

        return $this;
    }

    /**
     * Run the callback through the cache when caching is enabled
     */
    protected function remember(string $key, Closure $callback): mixed
    {
        if ($this->cacheDuration === null || $this->cacheDuration <= 0) {
            return $callback();
        }

        return Cache::remember($key, now()->addMinutes($this->cacheDuration), $callback);
    }
}
