<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;

class DatabaseCacheTest extends TestCase
{
    use WithLaravelMigrations;
    use WithFaker;
    use DatabaseMigrations;

    protected function setUp() : void
    {
        $this->afterApplicationCreated(function () {
            $this->loadLaravelMigrations();

            for ($i = 0; $i < 10; ++$i) {
                User::make()->forceFill([
                    'email' => $this->faker->freeEmail,
                    'name' => $this->faker->name,
                    'password' => 'password',
                    'email_verified_at' => today(),
                ])->save();
            }
        });

        parent::setUp();
    }

    public function test_eloquent_builder_cached(): void
    {
        $this->cache()->flush();

        $id = User::inRandomOrder()->cache(60)->value('id');

        User::destroy($id);

        static::assertEquals($id, User::inRandomOrder()->cache(60)->value('id'));

        $id = User::inRandomOrder()->cache(30, 'customKey')->value('id');

        User::destroy($id);

        static::assertEquals($id, User::inRandomOrder()->cache(30, 'customKey')->value('id'));

        $id = User::inRandomOrder()->cache(30, 'differentKey')->value('id');

        User::destroy($id);

        static::assertNotEquals($id, User::inRandomOrder()->cache(30, 'customKey')->value('id'));
    }

    public function test_query_returns_saved_null_result(): void
    {
        $this->cache()->flush();

        $id = User::where('name', 'not-exist')->cache(60)->first();

        User::make()->forceFill([
            'name' => 'not-exists',
            'email' => $this->faker->freeEmail,
            'password' => 'password',
            'email_verified_at' => today(),
        ])->save();

        static::assertNull($result = User::where('name', 'not-exist')->cache(60)->first());
        static::assertEquals($result, $id);
    }

    public function test_query_returns_raw_statement(): void
    {
        $this->cache()->flush();

        User::where(DB::raw("'name' <> NULL"))->cache(60)->first();

        User::truncate();

        static::assertNotNull(User::where(DB::raw("'name' <> NULL"))->cache(60)->first());
    }

    public function test_any_position(): void
    {
        $this->cache()->flush();

        static::assertEquals(
            User::inRandomOrder()->cache(60, 'test')->first(),
            User::cache(60, 'test')->inRandomOrder()->first()
        );
    }

    protected function cache(): Repository
    {
        return Cache::tags(['database']);
    }
}
