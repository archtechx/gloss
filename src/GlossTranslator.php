<?php

declare(strict_types=1);

namespace Lean\Gloss;

use Countable;
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
     * @param string $shortKey
     * @param string $newKey
     * @return void
     */
    public function key(string $shortKey, string $newKey)
    {
        $this->keyOverrides[$shortKey] = $newKey;
    }

    /**
     * Register an override that returns a value.
     *
     * @param string $shortKey
     * @param string $value
     * @return void
     */
    public function value(string $shortKey, string $value)
    {
        $this->valueOverrides[$shortKey] = $value;
    }

    /**
     * Register multiple value overrides.
     *
     * @param array $values
     * @return void
     */
    public function values(array $values)
    {
        foreach ($values as $key => $value) {
            $this->valueOverrides[$key] = $value;
        }
    }

    /**
     * Customize a translation string's value using a callback.
     *
     * @param string $shortKey
     * @param callable $value
     * @return void
     */
    public function extend(string $shortKey, callable $value)
    {
        $this->extensions[$shortKey][] = $value;
    }

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

    protected function getWithoutExtensions($key, $replace = [], $locale = null, $fallback = true)
    {
        return array_key_exists($key, $this->keyOverrides)
            ? $this->get($this->keyOverrides[$key])
            : $this->valueOverrides[$key]
            ?? parent::get($key, $replace, $locale, $fallback);
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
            $key, $replace, $locale = $this->localeForChoice($locale)
        );

        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        $replace['count'] = $number;

        return $this->makeReplacements(
            $this->getSelector()->choose($line, $number, $locale), $replace
        );
    }
}
