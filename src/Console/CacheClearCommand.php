<?php declare(strict_types=1);

namespace Eusonlito\DatabaseCache\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;

class CacheClearCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'database-cache:clear {tag? : The specific table tag to clear (e.g., users)}';

    /**
     * @var string
     */
    protected $description = 'Clear database query cache by tag or all';

    /**
     * @var array
     */
    protected array $config;

    /**
     * @return int
     */
    public function handle(): int
    {
        $tag = $this->argument('tag');

        if ($tag) {
            return $this->clearTag($tag);
        }

        return $this->clearAll();
    }

    /**
     * @param string $tag
     *
     * @return int
     */
    protected function clearTag(string $tag): int
    {
        $repository = $this->repository();

        if ($repository->supportsTags() === false) {
            $this->error('Cache driver does not support tags.');

            return self::FAILURE;
        }

        $fullTag = $this->tagGlobal().'|'.$tag;

        $repository->tags($fullTag)->flush();

        $this->info('Database cache cleared for tag: '.$fullTag);

        return self::SUCCESS;
    }

    /**
     * @return int
     */
    protected function clearAll(): int
    {
        $repository = $this->repository();

        if ($repository->supportsTags() === false) {
            $this->error('Cache driver does not support tags.');

            return self::FAILURE;
        }

        $tag = $this->tagGlobal();

        $repository->tags($tag)->flush();

        $this->info('All database cache cleared for tag: '.$tag);

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function repository(): Repository
    {
        return resolve('cache')->store($this->config('driver'));
    }

    /**
     * @return string
     */
    protected function tagGlobal(): string
    {
        return $this->config('tag');
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
        return $this->config ??= config('database-cache', []) + require dirname(__DIR__, 2).'/config/database-cache.php';
    }
}

