<?php

declare(strict_types=1);

namespace Lean\Gloss;

use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

class GlossServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Gloss::$containerKey, function ($app) {
            /** @var Translator $translator */
            $translator = $app['translator'];

            $trans = new GlossTranslator($translator->getLoader(), $translator->getLocale());

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });

        if (Gloss::$shouldReplaceTranslator) {
            $this->app->extend('translator', fn () => $this->app->make(Gloss::$containerKey));
        }
    }
}
