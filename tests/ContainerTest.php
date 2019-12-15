<?php

/**
 * Container
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Gustavo Fabiane (c) 2019
 */

declare(strict_types=1);

namespace GustavoFabiane\Tests\Container;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use GustavoFabiane\Container\Tests\Stubs\StubInterface;
use GustavoFabiane\Container\Tests\Stubs\SimpleConstructorStub;
use GustavoFabiane\Container\Tests\Stubs\ServiceStub;
use GustavoFabiane\Container\Tests\Stubs\InterfaceStub;
use GustavoFabiane\Container\Tests\Stubs\ClassInjectableStub;
use GustavoFabiane\Container\Tests\Stubs\CallableMethodsStub;
use GustavoFabiane\Container\EntryNotFoundException;
use GustavoFabiane\Container\ContainerException;
use GustavoFabiane\Container\Container;

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
        
        $instance = $container->make($abstract, $params);
        $this->assertInstanceOf($abstract, $instance);
    }
    
    /**
     * @dataProvider abstractAndParametersProvider
     *
     * @param string $abstract
     * @param array $params
     * @return void
     */
    public function testMakeInstanceFullRuntimeResolving(string $abstract, array $params): void
    {
        $container = new Container();
        
        $instance = $container->make($abstract, $params);
        $this->assertInstanceOf($abstract, $instance);
    }

    /**
     * @return array
     */
    public function abstractAndParametersProvider(): array
    {
        return [
            [\stdClass::class, []],
            [SimpleConstructorStub::class, ['a' => 1, 'b' => 2]],
            [ClassInjectableStub::class, []],
            [ServiceStub::class, []],
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

    /**
     * @return void
     */
    public function testCallClosure(): void
    {
        $container = new Container();

        $container->call(function () {
            $this->assertTrue(true);
        });
    }

    /**
     * @return void
     */
    public function testCallMethod(): void
    {
        $container = new Container();

        $result = $container->call([CallableMethodsStub::class, 'stub']);
        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function testCallMethodWithArg(): void
    {
        $container = new Container();
        $option = ['a' => 'b'];

        $result = $container->call([CallableMethodsStub::class, 'stubWithArg'], compact('option'));
        $this->assertEquals($option, $result);
    }

    /**
     * @return void
     */
    public function testCallMethodWithContainerEntry(): void
    {
        $container = new Container();
        $container->set('option', ['a' => 'b']);

        $result = $container->call([CallableMethodsStub::class, 'stubWithArg']);
        $this->assertEquals($container->get('option'), $result);
    }

    /**
     * @return void
     */
    public function testCallMethodWithMultipleArgs(): void
    {
        $container = new Container();
        $option = ['a' => 'b'];
        $container->set('object', $object = new \stdClass());

        $result = $container->call([CallableMethodsStub::class, 'setubWithMultipleArgs'], compact('option'));
        $this->assertSame($object, $result['object']);
    }

    /**
     * @return void
     */
    public function testCallInvokableClass(): void
    {
        $container = new Container();

        $result = $container->call(CallableMethodsStub::class);
        $this->assertIsArray($result);
    }

    /**
     * @return void
     */
    public function testCallWithCustomDefaultMethod(): void
    {
        $container = new Container();

        $result = $container->call(CallableMethodsStub::class, [], 'stub');
        $this->assertTrue($result);
    }

    public function testCallUndefinedMethod(): void
    {
        $this->expectException(ContainerException::class);
        
        $container = new Container();
        $container->call(CallableMethodsStub::class, [], 'stubUndefined');
    }

    public function testCallUndefinedFunction(): void
    {
        $this->expectException(ContainerException::class);
        
        $container = new Container();
        $container->call('functionDatDoesNotExists', [], 'stubUndefined');
    }
}
