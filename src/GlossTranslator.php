<?php

declare(strict_types=1);

namespace Lean\Gloss;

use Countable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Translation\Translator;

class GlossTranslator extends Translator
{
    /** Overrides that refer to a different key. */
    public array $keyOverrides = [];

    /** Overrides with new values. */
    public array $valueOverrides = [];

    /** Extensions executed after the string is built. */
    public array $extensions = [];

    /**
     * Register an override that returns a different key name.
     *
     * @param array|null|callable $condition
     * @return void
     */
    public function key(string $shortKey, string $newKey, $condition = null)
    {
        if ($condition === null) {
            $condition = fn () => true;
        } elseif (! is_callable($condition)) {
            $condition = fn ($data) => array_intersect_assoc($data, $condition) !== [];
        }

        $this->keyOverrides[$shortKey][] = [
            'condition' => $condition,
            'value' => $newKey,
        ];
    }

    /**
     * Register an override that returns a value.
     *
     * @param array|null|callable $condition
     * @return void
     */
    public function value(string $shortKey, string $value, $condition = null)
    {
        if ($condition === null) {
            $condition = fn () => true;
        } elseif (! is_callable($condition)) {
            $condition = fn ($data) => array_intersect_assoc($data, $condition) !== [];
        }

        $this->valueOverrides[$shortKey][] = [
            'condition' => $condition,
            'value' => $value,
        ];
    }

    /**
     * Register multiple value overrides.
     *
     * @param array|null|callable $condition
     * @return void
     */
    public function values(array $values, $condition = null)
    {
        /** @var string $key */
        foreach ($values as $key => $value) {
            $this->value($key, $value, $condition);
        }
    }

    /**
     * Customize a translation string's value using a callback.
     *
     * @return void
     */
    public function extend(string $shortKey, callable $value)
    {
        $this->extensions[$shortKey][] = $value;
    }

    /**
     * Get a translation string.
     *
     * @param string $key
     * @param string|null $locale
     * @param bool $fallback
     * @return string
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        if (array_key_exists($key, $this->extensions)) {
            // We recursively call the same method, but we make sure to skip this branch.
            $stringWithoutReplacedVariables = $this->getWithoutExtensions($key, [], $locale, $fallback);

            $replacer = function (string $string, array $replacements) {
                foreach ($replacements as $from => $to) {
                    $string = str_replace($from, $to, $string);
                }

                return $string;
            };

            // We run all of the extend() callbacks
            $extendedString = $key;
            foreach ($this->extensions[$key] as $extension) {
                $extendedString = $extension($stringWithoutReplacedVariables, $replacer);
            }

            // Finally, we run the string through trans() once again
            // to do the replacements in Laravel and potentially
            // catch edge case overrides for values in Gloss.
            $key = $extendedString;
        }

        return $this->getWithoutExtensions($key, $replace, $locale, $fallback);
    }

    /**
     * Get a translation string and skip extensions.
     *
     * @param array $replace
     * @param string|null $locale
     * @param bool $fallback
     * @return string
     */
    protected function getWithoutExtensions(string $key, $replace = [], $locale = null, $fallback = true)
    {
        return $this->getKeyOverride($key, $replace)
            ?? $this->getValueOverride($key, $replace)
            ?? parent::get($key, $replace, $locale, $fallback);
    }

    protected function getKeyOverride(string $key, array $data)
    {
        if (isset($this->keyOverrides[$key])) {
            foreach ($this->keyOverrides[$key] as $override) {
                if ($override['condition']($data)) {
                    return $this->get($override['value'], $data);
                }
            }
        }

        return null;
    }

    protected function getValueOverride(string $key, array $data)
    {
        if (isset($this->valueOverrides[$key])) {
            foreach ($this->valueOverrides[$key] as $override) {
                if ($override['condition']($data)) {
                    return $this->get($override['value'], $data);
                }
            }
        }

        return null;
    }

    public function choice($key, $number, array $replace = [], $locale = null)
    {
        if (array_key_exists($key, $this->extensions)) {
            // We recursively call the same method, but we make sure to skip this branch.
            $stringWithoutReplacedVariables = $this->getWithoutExtensions($key, [], $locale);

            $replacer = function (string $string, array $replacements) {
                foreach ($replacements as $from => $to) {
                    $string = str_replace($from, $to, $string);
                }

                return $string;
            };

            // We run all of the extend() callbacks
            $extendedString = $key;
            foreach ($this->extensions[$key] as $extension) {
                $extendedString = $extension($stringWithoutReplacedVariables, $replacer);
            }

            // Finally, we run the string through trans() once again
            // to do the replacements in Laravel and potentially
            // catch edge case overrides for values in Gloss.
            $key = $extendedString;
        }

        return $this->choiceWithoutExtensions($key, $number, $replace, $locale);
    }

    protected function choiceWithoutExtensions($key, $number, array $replace = [], $locale = null)
    {
        $line = $this->getWithoutExtensions(
            $key,
            $replace,
            $locale = $this->localeForChoice($locale)
        );

        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;

        return $this->makeReplacements(
            $this->getSelector()->choose($line, $number, $locale),
            $replace
        );
    }

    protected function getLine($namespace, $group, $locale, $item, array $replace)
    {
        $this->load($namespace, $group, $locale);

        $line = Arr::get($this->loaded[$namespace][$group][$locale], $item);

        if (is_string($line) || is_callable($line)) { // Changed
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            return $this->makeReplacementsInArray($line, $replace);
        }

        return null;
    }

    protected function makeReplacementsInArray(array $lines, array $replace): array
    {
        foreach ($lines as $key => $value) {
            if (is_array($value)) {
                $value = $this->makeReplacementsInArray($value, $replace);
            } else {
                $value = $this->makeReplacements($value, $replace);
            }

            $lines[$key] = $value;
        }

        return $lines;
    }

    /**
     * @param string|callable $line
     * @return string
     */
    protected function makeReplacements($line, array $replace)
    {
        if (is_callable($line) && ! is_string($line)) {
            try {
                $line = app()->call($line, $replace);
            } catch (BindingResolutionException $exception) {
                // We keep it a Closure if we can't safely call it
            }
        }

        return parent::makeReplacements($line, $replace);
    }
}
