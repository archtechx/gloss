<?php

namespace Lean\Gloss\Tests;

use Lean\Gloss\Gloss;
use Lean\Gloss\GlossServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // @phpstan-ignore-next-line
        $this->app->bind('translation.loader', GlossLoader::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            GlossServiceProvider::class,
        ];
    }
}
