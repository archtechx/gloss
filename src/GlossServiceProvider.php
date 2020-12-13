<?php

declare(strict_types=1);

namespace Lean\Gloss;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class GlossServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Gloss::$containerKey, function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new GlossTranslator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        if (Gloss::$shouldReplaceTranslator) {
            $this->app->extend('translator', fn () => $this->app->make(Gloss::$containerKey));
        }
    }
}
