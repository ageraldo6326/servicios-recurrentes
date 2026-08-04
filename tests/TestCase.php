<?php

namespace Tests;

use LogicException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (
            config('app.env') !== 'testing'
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new LogicException('Tests require APP_ENV=testing and the isolated in-memory SQLite database.');
        }
    }
}
