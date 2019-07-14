<?php

/**
 * Simple Way PHP
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://simplewayphp.dev
 */

declare(strict_types=1);

namespace SimpleWay\Tests\Container;

use SimpleWay\Container\Tests\Stubs\StubInterface;
use SimpleWay\Container\Tests\Stubs\SimpleConstructorStub;
use SimpleWay\Container\Tests\Stubs\ServiceStub;
use SimpleWay\Container\Tests\Stubs\InterfaceStub;
use SimpleWay\Container\Tests\Stubs\ClassInjectableStub;
use SimpleWay\Container\EntryNotFoundException;
use SimpleWay\Container\ContainerException;
use SimpleWay\Container\Container;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * Create a container instance
     *
     * @return Container
     */
    public function testCreateInstance(): Container
    {
        $container = new Container();

        $this->assertInstanceOf(Container::class, $container);
        $this->assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testSetAndGetEntry(Container $container): void
    {
        $container->set('a', $obj = new \stdClass());
        $this->assertSame($obj, $container->get('a'));
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testEntryInstancePersistence(Container $container): void
    {
        $container->set('b', new \stdClass());
        $this->assertSame(
            $container->get('b'),
            $container->get('b')
        );
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testSetFactory(Container $container): void
    {
        $container->factory('c', function () {
            return new \stdClass();
        });

        $this->assertNotSame(
            $container->get('c'),
            $container->get('c'),
            'Container factories must always initializes a new class instance'
        );
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testHasEntry(Container $container): void
    {
        $container->set('d', new \stdClass());

        $this->assertTrue($container->has('d'));
        $this->assertFalse($container->has('e'));
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testGetNonExistentEntry(Container $container): void
    {
        $this->expectException(EntryNotFoundException::class);
        $container->get('f');
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testSetInstance(Container $container): void
    {
        $container->instance('g', $instance = new ServiceStub());
        $this->assertSame($instance, $container->get('g'));
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testSetAlias(Container $container): void
    {
        $container->set('h', function () {
            return new \stdClass();
        });
        $container->alias('h', 'alias');

        $this->assertEquals(
            $container->get('alias'),
            $container->get('h')
        );
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testSetInterface(Container $container): void
    {
        $container->set('i', function () {
            return new InterfaceStub();
        });
        $container->interface('i', StubInterface::class);

        $this->assertEquals(
            $container->get(StubInterface::class),
            $container->get('i')
        );
    }

    /**
     * @depends testCreateInstance
     *
     * @param Container $container
     * @return void
     */
    public function testSetInvalidInterface(Container $container): void
    {
        $this->expectException(ContainerException::class);

        $container->set('i', function () {
            return new InterfaceStub();
        });
        $container->interface('i', 'AnyInexistentInterface');
    }
    
    /**
     * @dataProvider abstractAndParametersProvider
     *
     * @param string $abstract
     * @param array $params
     * @return void
     */
    public function testMakeInstance(string $abstract, array $params): void
    {
        $container = new Container();
        $container->set(ServiceStub::class, function () {
            return new ServiceStub();
        });
        $container->set('x', function () {
            return new \stdClass();
        });
        
        $instance = $container->make($abstract, $params);
        $this->assertInstanceOf($abstract, $instance);
    }

    public function abstractAndParametersProvider(): array
    {
        return [
            [\stdClass::class, []],
            [SimpleConstructorStub::class, ['a' => 1, 'b' => 2]],
            [ClassInjectableStub::class, []],
            [ServiceStub::class, []]
        ];
    }

    /**
     * @return void
     */
    public function testMakeInvalidClassInstance(): void
    {
        $this->expectException(ContainerException::class);
        
        $container = new Container();
        $container->make(uniqid());
    }
}
