<?php declare(strict_types=1);

namespace Eusonlito\DatabaseCache;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Query\Builder;

class CacheBuilder extends Builder
{
    /**
     * @var array
     */
    protected array $cacheConfig;

    /**
     * @var int|\DateTimeInterface|\DateInterval|null
     */
    protected int|DateTimeInterface|DateInterval|null $cacheTTL = 0;

    /**
     * @var ?string
     */
    protected ?string $cacheKey;

    /**
     * @return array
     */
    protected function runSelect()
    {
        return $this->cacheResult(fn () => parent::runSelect());
    }

    /**
     * @param int|\DateTimeInterface|\DateInterval|null $ttl = null
     * @param ?string $key = null
     *
     * @return self
     */
    public function cache(int|DateTimeInterface|DateInterval|null $ttl = null, ?string $key = null): self
    {
        $this->cacheTTL = $ttl ?? $this->cacheConfig('ttl');
        $this->cacheKey = $key;

        return $this;
    }

    /**
     * @return self
     */
    protected function cacheReset(): self
    {
        $this->cacheTTL = null;
        $this->cacheKey = null;

        return $this;
    }

    /**
     * @param callable $result
     *
     * @return array
     */
    protected function cacheResult(callable $result): array
    {
        $response = fn () => tap($result(), fn () => $this->cacheReset());

        if ($this->cacheEnabled() === false) {
            return $response();
        }

        return $this->cacheManager()->remember($this->cacheKey(), $this->cacheTTL(), $response);
    }

    /**
     * @return bool
     */
    protected function cacheEnabled(): bool
    {
        return $this->cacheConfig('enabled') && $this->cacheTTL();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function cacheConfig(string $key): mixed
    {
        return $this->cacheConfigDefault()[$key] ?? null;
    }

    /**
     * @return array
     */
    protected function cacheConfigDefault(): array
    {
        static $config;

        return $config ??= config('database-cache', [])
            + require dirname(__DIR__).'/config/database-cache.php';
    }

    /**
     * @return string
     */
    protected function cacheKey(): string
    {
        return $this->cacheKey ?? $this->cacheConfig('prefix').md5($this->toSql().'|'.serialize($this->getBindings()));
    }

    /**
     * @return int|\DateTimeInterface|\DateInterval|null
     */
    protected function cacheTTL(): int|DateTimeInterface|DateInterval|null
    {
        return $this->cacheTTL;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cacheManager(): Repository
    {
        $repository = $this->cacheRepository();

        if ($repository->supportsTags() && $this->cacheTagGlobal()) {
            $repository = $repository->tags($this->cacheTags());
        }

        return $repository;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function cacheRepository(): Repository
    {
        static $repository;

        return $repository ??= resolve('cache')->store($this->cacheConfig('driver'));
    }

    /**
     * @return array
     */
    protected function cacheTags(): array
    {
        return array_filter([$this->cacheTagGlobal(), $this->cacheTagPrefix()]);
    }

    /**
     * @return ?string
     */
    protected function cacheTagGlobal(): ?string
    {
        return $this->cacheConfig('tag');
    }

    /**
     * @return ?string
     */
    protected function cacheTagPrefix(): ?string
    {
        if ($this->from) {
            return $this->cacheTagGlobal().'|'.$this->from;
        }

        return null;
    }
}
