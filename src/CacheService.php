<?php declare(strict_types=1);

namespace Eusonlito\DatabaseCache;

use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Query\Builder;

class CacheService
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected Repository $repository;

    /**
     * @var \Illuminate\Database\Query\Builder
     */
    protected Builder $builder;

    /**
     * @var int|\DateTimeInterface|\DateInterval|null
     */
    protected int|DateTimeInterface|DateInterval|null $ttl = 0;

    /**
     * @var ?string
     */
    protected ?string $key;

    /**
     * @param int|\DateTimeInterface|\DateInterval|null $ttl = null
     * @param ?string $key = null
     *
     * @return self
     */
    public function __construct(Builder $builder, int|DateTimeInterface|DateInterval|null $ttl = null, ?string $key = null)
    {
        $this->builder = $builder;
        $this->ttl = $ttl ?? $this->config('ttl');
        $this->key = $key;

        return $this;
    }

    /**
     * @param callable $result
     *
     * @return array
     */
    public function get(callable $result): array
    {
        if ($this->enabled() === false) {
            return $result();
        }

        return $this->manager()->remember($this->key(), $this->ttl(), $result);
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->config('enabled') && $this->ttl();
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function config(string $key): mixed
    {
        return $this->configDefault()[$key] ?? null;
    }

    /**
     * @return array
     */
    protected function configDefault(): array
    {
        return $this->config ??= config('database-cache', []) + require dirname(__DIR__).'/config/database-cache.php';
    }

    /**
     * @return string
     */
    protected function key(): string
    {
        return $this->key ?? $this->config('prefix').md5($this->builder->toSql().'|'.serialize($this->builder->getBindings()));
    }

    /**
     * @return int|\DateTimeInterface|\DateInterval|null
     */
    protected function ttl(): int|DateTimeInterface|DateInterval|null
    {
        return $this->ttl;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function manager(): Repository
    {
        $repository = $this->repository();

        if ($repository->supportsTags() && $this->tagGlobal()) {
            $repository = $repository->tags($this->tags());
        }

        return $repository;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function repository(): Repository
    {
        return $this->repository ??= resolve('cache')->store($this->config('driver'));
    }

    /**
     * @return array
     */
    protected function tags(): array
    {
        return array_filter([$this->tagGlobal(), $this->tagPrefix()]);
    }

    /**
     * @return ?string
     */
    protected function tagGlobal(): ?string
    {
        return $this->config('tag');
    }

    /**
     * @return ?string
     */
    protected function tagPrefix(): ?string
    {
        if ($this->builder->from) {
            return $this->tagGlobal().'|'.$this->builder->from;
        }

        return null;
    }
}
