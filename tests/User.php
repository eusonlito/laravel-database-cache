<?php declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Auth\User as UserAuth;
use Eusonlito\DatabaseCache\CacheBuilderTrait;

class User extends UserAuth
{
    use CacheBuilderTrait;
}
