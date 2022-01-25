# Database Query Cache

```php
Articles::latest('published_at')->take(10)->cache()->get();
```

## Requirements

* PHP 8.0
* Laravel 8.x

## Installation

You can install the package via composer:

```bash
composer require eusonlito/laravel-database-cache

php artisan vendor:publish --tag=eusonlito-database-cache
```

Add the `Eusonlito\DatabaseCache\CacheBuilderTrait` to your model:

```php
<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Eusonlito\DatabaseCache\CacheBuilderTrait;

class User extends Model
{
    use CacheBuilderTrait;
```

## Configuration

Default configuration values are set in `config/database-cache.php` file:

```php
return [
    'enabled' => (bool)env('DATABASE_CACHE_ENABLED', env('CACHE_ENABLED', true)),
    'driver' => env('DATABASE_CACHE_DRIVER', env('CACHE_DRIVER', 'redis')),
    'ttl' => (int)env('DATABASE_CACHE_TTL', env('CACHE_TTL', 3600)),
    'tag' => env('DATABASE_CACHE_TAG', 'database'),
    'prefix' => env('DATABASE_CACHE_PREFIX', 'database|'),
];
```

Also you can set custom `ttl` and `key` on every `->cache(:ttl, :key)` call.

## Usage

Just use the `cache()` method to remember a Query result **before the execution**. That's it. The method automatically remembers the result for 3600 seconds.

If you are using the default config, this cache will be stored inside `['database', 'database|articles']` tags.

```php
use App\Models\Article;

$articles = Article::latest('published_at')->take(10)->cache()->get();
```

The next time you call the **same** query, the result will be retrieved from the cache instead of running the SQL statement in the database, even if the result is `null` or `false`.

> The `cache()` will throw an error if you build a query instead of executing it.

### Time-to-live

By default, queries are remembered by 60 seconds, but you're free to use any length, `Datetime`, `DateInterval` or Carbon instance.

```php
Article::latest('published_at')->take(10)->cache(now()->addHour())->get();
```

### Custom Cache Key

The auto-generated cache key is an BASE64-MD5 hash of the SQL query and its bindings, which avoids any collision with other queries while keeping the cache key short. 

If you are using the default config, this cache will be stored inside `['database', 'database|articles']` tags with the key `latest_articles`.

```php
Article::latest('published_at')->take(10)->cache(30, 'latest_articles')->get();
```

## Operations are **NOT** commutative

Altering the Builder methods order will change the auto-generated cache key hash. Even if they are _visually_ the same, the order of statements makes the hash completely different.

For example, given two similar queries in different parts of the application, these both will **not** share the same cached result:

```php
User::whereName('Joe')->whereAge(20)->cache()->first();
User::whereAge(20)->whereName('Joe')->cache()->first();
```

To ensure you're hitting the same cache on similar queries, use a [custom cache key](#custom-cache-key). With this, all queries using the same key will share the same cached result:

```php
User::whereName('Joe')->whereAge(20)->cache(60, 'find_joe')->first();
User::whereAge(20)->whereName('Joe')->cache(60, 'find_joe')->first();
```

This will allow you to even retrieve the data outside the query, by just asking directly to the cache.

```php
$joe = Cache::tags(['database', 'database|users'])->get('find_joe');
```

Remember that you need to pass the same ordered list of tags to the `tags` method as when cache was stored. Always use `['database', 'database|XXX']` when `XXX` is the table name related with the query.

## Tags

This package tag caches with two different [tags](https://laravel.com/docs/8.x/cache#cache-tags) (only supported by `redis` and `memcached`)

* `database` is the global for all database cache.
* `database|XXXX` is the tag for every different table. Table name will be set with the `from` string on `Query Builder`.

## Flush caches

You can flush all database caches or only caches related with only one table:

```php
// Flush all database cache
Cache::tags('database')->flush();

// Flush only users table cache
Cache::tags('database|users')->flush();
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
