<?php

declare(strict_types=1);

use Lean\Gloss\Gloss;

if (! function_exists('gloss')) {
    /**
     * Resolve a translation string or Gloss instance.
     *
     * @param string|array|null $key
     * @param array $replace
     * @param string|null $locale
     * @return void|string|null|\Lean\Gloss\GlossTranslator
     */
    function gloss($key = null, array $replace = [], string $locale = null)
    {
        if (is_array($key)) {
            Gloss::values($key);

            return;
        }

        if (is_string($key)) {
            return Gloss::get($key, $replace, $locale);
        }

        return Gloss::getFacadeRoot();
    }
}

if (! function_exists('___') && Gloss::$underscoreHelper) {
    /**
     * Resolve a translation string or Gloss instance.
     *
     * @param string|array|null $key
     * @param array $replace
     * @param string|null $locale
     * @return void|string|null|\Lean\Gloss\GlossTranslator
     */
    function ___($key = null, array $replace = [], string $locale = null)
    {
        return gloss($key, $replace, $locale);
    }
}
