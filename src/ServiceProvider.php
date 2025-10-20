<?php declare(strict_types=1);

namespace Eusonlito\DatabaseCache;

use Eusonlito\DatabaseCache\Console\CacheClearCommand;
use Illuminate\Support\ServiceProvider as ServiceProviderVendor;

class ServiceProvider extends ServiceProviderVendor
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->configPublish();
        $this->commandsRegister();
    }

    /**
     * @return void
     */
    protected function configPublish(): void
    {
        $this->publishes([
            dirname(__DIR__).'/config/database-cache.php' => config_path('database-cache.php'),
        ], 'eusonlito-database-cache');
    }

    /**
     * @return void
     */
    protected function commandsRegister(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CacheClearCommand::class,
            ]);
        }
    }
}
