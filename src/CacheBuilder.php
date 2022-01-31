<?php declare(strict_types=1);

namespace Eusonlito\DatabaseCache;

use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;

class CacheBuilder extends Builder
{
    /**
     * @var ?\Eusonlito\DatabaseCache\CacheService
     */
    protected ?CacheService $cache = null;

    /**
     * @return array
     */
    protected function runSelect()
    {
        $result = fn () => parent::runSelect();

        if ($this->cache === null) {
            return $result();
        }

        return $this->cache->get($result);
    }

    /**
     * @param int|\DateTimeInterface|\DateInterval|null $ttl = null
     * @param ?string $key = null
     *
     * @return self
     */
    public function cache(int|DateTimeInterface|DateInterval|null $ttl = null, ?string $key = null): self
    {
        if (empty($this->cache) || ($ttl !== null)) {
            $this->cache = new CacheService($this, $ttl, $key);
        }

        return $this;
    }
}
