<?php

declare(strict_types=1);

namespace Lean\Gloss;

use Illuminate\Support\Facades\Facade;

/**
 * 🔍 Gloss ✨ — Brilliant localization for Laravel.
 *
 * @method static void key(string $shortKey, string $newKey, array|null|callable $condition) Set a key override.
 * @method static void value(string $shortKey, string $value, array|null|callable $condition) Set a value override.
 * @method static void values(string $shortKey, array|null|callable $condition) Set multiple value overrides.
 * @method static ?string get($key, $replace = [], $locale = null) Get a translation string.
 * @method static ?string choice($key, $replace = [], $locale = null) Get a translation according to an integer value.
 * @method static void extend(string $shortKey, callable(string, callable): string $value) Extend a translation string.
 *
 * @see \Lean\Gloss\GlossTranslator
 */
class Gloss extends Facade
{
    /**
     * The key used to bind Gloss to the service container.
     */
    public static string $containerKey = 'gloss';

    /**
     * Should ___() be used as a helper?
     */
    public static bool $underscoreHelper = true;

    /**
     * Should the Translator instance be replaced by Gloss?
     */
    public static bool $shouldReplaceTranslator = false;

    protected static function getFacadeAccessor()
    {
        return static::$containerKey;
    }
}
