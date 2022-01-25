<?php declare(strict_types=1);

namespace Eusonlito\DatabaseCache;

trait CacheBuilderTrait
{
    /**
     * @return \Eusonlito\DatabaseCache\CacheBuilder
     */
    protected function newBaseQueryBuilder()
    {
        return new CacheBuilder(($connection = $this->getConnection()), $connection->getQueryGrammar(), $connection->getPostProcessor());
    }
}
