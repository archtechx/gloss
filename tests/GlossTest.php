<?php

namespace Lean\Gloss\Tests;

use Lean\Gloss\Gloss;
use Lean\Gloss\GlossServiceProvider;
use Lean\Gloss\GlossTranslator;

class GlossTest extends TestCase
{
    /**
     * @internal
     * @test
     */
    public function addMessage_works()
    {
        $this->addMessage('foo', 'bar');

        $this->assertSame('bar', gloss('test.foo'));
    }

    /**
     * @internal
     * @test
     */
    public function locale_can_be_changed_halfway_through_a_test()
    {
        $this->addMessage('foo', 'english', 'en');
        $this->addMessage('foo', 'czech', 'cs');

        $this->assertSame('english', gloss('test.foo'));

        gloss()->setLocale('cs');

        $this->assertSame('czech', gloss('test.foo'));
    }

    /** @test */
    public function value_can_be_replaced()
    {
        $this->addMessage(
            'resource.create',
            'Create :resource'
        );

        Gloss::value('test.resource.create', 'Create my resource');

        $this->assertNotSame('Create foo', Gloss::get('test.resource.create', ['resource' => 'foo']));
        $this->assertSame('Create my resource', Gloss::get('test.resource.create', ['resource' => 'foo']));
    }

    /** @test */
    public function short_key_can_be_replaced()
    {
        $this->addMessages('en', 'test', [
            'resource.create' => 'Create :resource',
            'foo.create' => 'Foo/Create',
        ]);

        Gloss::key('test.resource.create', 'test.foo.create');

        $this->assertNotSame('Create foo', Gloss::get('test.resource.create', ['resource' => 'foo']));
        $this->assertSame('Foo/Create', Gloss::get('test.resource.create', ['resource' => 'foo']));
    }

    /** @test */
    public function key_overrides_work_recursively()
    {
        $this->addMessages('en', 'test', [
            'resources.create' => 'Create :resource',
            'foo.create' => 'Foo/Create',
            'foo.create_new' => 'Foo/Create/New',
        ]);

        Gloss::key('test.resource.create', 'test.foo.create');
        Gloss::key('test.foo.create', 'test.foo.create_new');

        $this->assertNotSame('Create foo', Gloss::get('test.resource.create', ['resource' => 'foo']));
        $this->assertNotSame('Create/Create', Gloss::get('test.resource.create', ['resource' => 'foo']));
        $this->assertSame('Foo/Create/New', Gloss::get('test.resource.create', ['resource' => 'foo']));
    }

    /** @test */
    public function value_overrides_dont_work_recursively()
    {
        $this->addMessages('en', 'test', [
            'Create :resource' => 'not called',
            'Create Foo' => 'not called',
        ]);

        Gloss::value('Create :resource', 'Create :Resource');

        $this->assertNotSame('not called', Gloss::get('Create :resource', ['resource' => 'foo']));
        $this->assertSame('Create :Resource', Gloss::get('Create :resource', ['resource' => 'foo']));
    }

    /** @test */
    public function keys_can_be_extended()
    {
        $this->addMessage('pagination', 'Showing :start to :end of :total results', 'en');
        $this->addMessage('pagination', 'Zobrazeno :start až :end z :total výsledků', 'cs');

        Gloss::extend('test.pagination', fn ($value, $replace) => $replace($value, [
            ':start' => '<span class="font-medium">:start</span>',
            ':end' => '<span class="font-medium">:end</span>',
            ':total' => '<span class="font-medium">:total</span>',
        ]));

        $this->assertSame(
            'Showing <span class="font-medium">10</span> to <span class="font-medium">20</span> of <span class="font-medium">50</span> results',
            Gloss::get('test.pagination', ['start' => 10, 'end' => 20, 'total' => 50])
        );

        gloss()->setLocale('cs');

        $this->assertSame(
            'Zobrazeno <span class="font-medium">10</span> až <span class="font-medium">20</span> z <span class="font-medium">50</span> výsledků',
            Gloss::get('test.pagination', ['start' => 10, 'end' => 20, 'total' => 50])
        );
    }

    /** @test */
    public function values_can_be_extended()
    {
        $string = 'Showing :start to :end of :total results';

        Gloss::extend($string, fn ($value, $replace) => $replace($value, [
            ':start' => '<span class="font-medium">:start</span>',
            ':end' => '<span class="font-medium">:end</span>',
            ':total' => '<span class="font-medium">:total</span>',
        ]));

        $this->assertSame(
            'Showing <span class="font-medium">10</span> to <span class="font-medium">20</span> of <span class="font-medium">50</span> results',
            Gloss::get($string, ['start' => 10, 'end' => 20, 'total' => 50])
        );
    }

    /** @test */
    public function gloss_helper_can_be_used()
    {
        $this->addMessage('foo', 'bar', 'en');
        $this->addMessage('foo', 'baz', 'cs');

        $this->assertSame('bar', gloss('test.foo'));
        $this->assertSame('baz', gloss('test.foo', [], 'cs'));

        gloss(['test.foo' => 'xyz']);

        $this->assertSame('xyz', gloss('test.foo'));
        $this->assertSame('xyz', gloss('test.foo', [], 'cs'));
    }

    /** @test */
    public function ___helper_can_be_used()
    {
        $this->assertTrue(___() instanceof GlossTranslator);
    }

    /** @test */
    public function the_helper_can_return_the_object_instance()
    {
        $this->assertTrue(gloss() instanceof GlossTranslator);
    }

    /** @test */
    public function pluralization_is_supported()
    {
        $this->addMessage('apples', 'There is one apple|There are many apples', 'en');
        $this->addMessage('apples', 'Je tam jedno jablko|Je tam mnoho jablek', 'cs');

        $this->assertSame('There is one apple', gloss()->choice('test.apples', 1));
        $this->assertSame('There are many apples', gloss()->choice('test.apples', 2));

        gloss()->setLocale('cs');

        $this->assertSame('Je tam jedno jablko', gloss()->choice('test.apples', 1));
        $this->assertSame('Je tam mnoho jablek', gloss()->choice('test.apples', 2));
    }

    /** @test */
    public function value_replaces_work_with_choices()
    {
        $this->addMessage('apples', 'There is one apple|There are many apples');

        Gloss::value('test.apples', 'One apple|Many apples');

        $this->assertSame('One apple', gloss()->choice('test.apples', 1));
        $this->assertSame('Many apples', gloss()->choice('test.apples', 2));
    }

    /** @test */
    public function key_replaces_work_with_choices()
    {
        $this->addMessage('apples', '{1} Je tam jedno jablko|[2,*]Je tam mnoho jablek');
        $this->addMessage('apples_with_0', '{0} Není tam žádné jablko|{1} Je tam jedno jablko|[2,*]Je tam mnoho jablek');

        Gloss::key('test.apples', 'test.apples_with_0');

        $this->assertSame('Není tam žádné jablko', gloss()->choice('test.apples', 0));
    }

    /** @test */
    public function extend_works_with_choices()
    {
        $this->addMessage('apples', '{0} There are no apples|[1,*]There are :count apples', 'en');
        $this->addMessage('apples', '{0} Není tam žádné jablko|[1,*]Je tam :count jablek', 'cs');

        Gloss::extend('test.apples', fn ($apples, $replace) => $replace($apples, [
            ':count' => '<span class="font-medium">:count</span>',
        ]));

        $this->assertSame('There are no apples', gloss()->choice('test.apples', 0));
        $this->assertSame('There are <span class="font-medium">2</span> apples', gloss()->choice('test.apples', 2));
    }

    protected function addMessage(string $key, string $value, string $locale = 'en', string $group = 'test', string $namespace = null): void
    {
        $this->addMessages($locale, $group, [$key => $value], $namespace);
    }

    protected function addMessages(string $locale, string $group, array $messages, string $namespace = null): void
    {
        /** @var GlossTranslator $translator */
        $translator = gloss();

        /** @var GlossLoader $loader */
        $loader = $translator->getLoader();

        $loader->addMessages($locale, $group, $messages, $namespace);
    }
}
