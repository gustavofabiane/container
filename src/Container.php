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

namespace GustavoFabiane\Container;

use Closure;
use ReflectionType;
use ReflectionClass;
use ReflectionFunction;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private const SCALAR_TYPES = [
        'int',
        'integer',
        'float',
        'double',
        'bool',
        'boolean',
        'string',
        'array'
    ];

    /**
     * Registered container entries
     *
     * @var array
     */
    protected $entries = [];

    /**
     * A list of container entries identifiers that are factories
     *
     * Factories are entries which does not maintain resolved instances,
     * this way they are resolved every time their called.
     *
     * @var array
     */
    protected $factories = [];

    /**
     * A list of container entries identifier that were already resolved
     *
     * @var array
     */
    protected $resolved = [];

    /**
     * The resolved instances of the container entries
     *
     * @var array
     */
    protected $instances = [];

    /**
     * A list of container interface implementors
     *
     * @var array
     */
    protected $interfaces = [];

    /**
     * A list of container entries aliases
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * Returns true if the given container entry has already been resolved
     *
     * @param string $id
     * @return bool
     */
    protected function isResolved(string $id): bool
    {
        return in_array($id, $this->resolved);
    }

    /**
     * Returns true if the given entry has a factory
     *
     * @param string $id
     * @return bool
     */
    protected function isFactory(string $id): bool
    {
        return in_array($id, $this->factories);
    }

    /**
     * Returns true if the given entry is an interface
     *
     * @param string $id
     * @return bool
     */
    protected function isInterface(string $id): bool
    {
        return interface_exists($id) && array_key_exists($id, $this->interfaces);
    }

    /**
     * Returns true if the given entry is an alias
     *
     * @param string $id
     * @return bool
     */
    protected function isAlias(string $id): bool
    {
        return array_key_exists($id, $this->aliases);
    }

    /**
     * Set a new entry into the container
     *
     * @param string $id
     * @param mixed $resolver
     * @return void
     */
    public function set(string $id, $resolver): void
    {
        $this->entries[$id] = $resolver;
    }

    /**
     * Set a new entry into the container as a factory
     *
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function factory(string $id, callable $factory): void
    {
        $this->set($id, $factory);
        if (!$this->isFactory($id)) {
            $this->factories[] = $id;
        }
    }

    /**
     * Set a new entry into the container as an instance
     *
     * @param string $id
     * @param object $instance
     * @return void
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
        $this->resolved[] = $id;
    }

    /**
     * Set an alias to the given entry id
     *
     * @param string $id
     * @param string $alias
     * @return void
     */
    public function alias(string $id, string $alias): void
    {
        $this->assertHasEntry($id);
        $this->aliases[$alias] = $id;
    }

    /**
     * Set an entry as resolver for the given interface
     *
     * @param string $id
     * @param string $interface
     * @return void
     */
    public function interface(string $id, string $interface): void
    {
        if (!interface_exists($interface)) {
            throw new ContainerException(sprintf(
                'The interface bind name must be a real interface, in [%s => %s]',
                $id,
                $interface
            ));
        }
        $this->assertHasEntry($id);
        $this->interfaces[$interface] = $id;
    }
    
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        $this->assertHasEntry($id);
        
        if ($this->isAlias($id)) {
            return $this->get($this->aliases[$id]);
        }
        if ($this->isInterface($id)) {
            return $this->get($this->interfaces[$id]);
        }

        if ($this->isResolved($id)) {
            return $this->instances[$id];
        }

        $concrete = $this->entries[$id];
        if (is_callable($concrete)) {
            $concrete = call_user_func($concrete, $this);
        }
        
        if (!$this->isFactory($id)) {
            $this->instances[$id] = $concrete;
            $this->resolved[] = $id;
        }
        
        return $concrete;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->entries) ||
               array_key_exists($id, $this->instances) ||
               array_key_exists($id, $this->interfaces) ||
               array_key_exists($id, $this->aliases);
    }

    /**
     * Asserts if the container has the given entry
     *
     * @param string $id
     * @return void
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     */
    protected function assertHasEntry(string $id): void
    {
        if (!$this->has($id)) {
            throw new EntryNotFoundException($id);
        }
    }

    /**
     * Resolve and call the given callable with container entries and provided parameters
     *
     * @param mixed $callable
     * @param array $params
     * @param string $defaultMethod
     * @return mixed
     */
    public function call($callable, array $params = [], string $defaultMethod = '__invoke')
    {
        try {
            [$callable, $reflectedParameters] = $this->normalizeCallable($callable, $defaultMethod);
            return call_user_func_array(
                $callable,
                $this->resolveParameters($reflectedParameters, $params)
            );
        } catch (ReflectionException $e) {
            throw new ContainerException(
                sprintf('Cannot call [%s]', $callable),
                0,
                $e
            );
        }
    }

    /**
     * Normalize callable to resolvable pattern
     *
     * @param mixed $callable
     * @param string $defaultMethod
     * @return array the normalized callable and its reflected parameters
     */
    protected function normalizeCallable($callable, string $defaultMethod): array
    {
        $normalized = false;
        if ((is_string($callable) && function_exists($callable)) || $callable instanceof Closure) {
            $reflectedParameters = (new ReflectionFunction($callable))->getParameters();
            $normalized = true;
        } else {
            if (!is_array($callable) && (is_object($callable) || class_exists($callable))) {
                $callable = [$callable, $defaultMethod];
                $normalized = true;
            }
            if (is_array($callable)) {
                [$classObject, $method] = $callable;
                $reflectedParameters = (new ReflectionClass($classObject))->getMethod($method)->getParameters();
                if (!is_object($classObject)) {
                    $classObject = $this->make($classObject);
                }
                $callable = [$classObject, $method];
                $normalized = true;
            }
        }
        if (!$normalized) {
            throw new ContainerException(sprintf('Cannot normalize callable [%s]', $callable));
        }

        return [$callable, $reflectedParameters];
    }

    /**
     * Resolves a class to an instance with optional default parameters or automatic container injection
     *
     * @param string $abstract
     * @param array $params
     * @param bool $share
     * @return object
     * @throws ContainerException The given abstract is not resolvable
     */
    public function make(string $abstract, array $params = [], bool $share = true)
    {
        try {
            if ($this->has($abstract)) {
                return $this->get($abstract);
            }
            $resolved = $this->getInstance(new ReflectionClass($abstract), $params);
            if ($share) {
                $this->instances[$abstract] = $resolved;
                $this->resolved[] = $abstract;
            }
            return $resolved;
        } catch (ReflectionException $e) {
            throw new ContainerException(
                sprintf('Cannot resolve [%s]', $abstract),
                0,
                $e
            );
        }
    }

    /**
     * Get instance of item
     *
     * @param ReflectionClass $item
     * @return mixed
     * @throws ReflectionException The reflection feature did not work correctly
     */
    protected function getInstance(ReflectionClass $item, array $params = [])
    {
        $constructor = $item->getConstructor();
        if (is_null($constructor) || $constructor->getNumberOfRequiredParameters() == 0) {
            return $item->newInstance();
        }
        return $item->newInstanceArgs(
            $this->resolveParameters($constructor->getParameters(), $params)
        );
    }

    /**
     * Resolve a reflected parameter
     *
     * @param ReflectionParameter $param
     * @param array $params
     * @return mixed
     */
    private function resolveParameter(ReflectionParameter $param, array $params = [])
    {
        /** @var ReflectionNamedType|ReflectionType $type */
        $type = $param->getType();

        $paramName = $param->getName();
        $resolvedParam = null;
        if (array_key_exists($paramName, $params)) {
            $resolvedParam = $params[$paramName];
        } elseif ($this->has($paramName)) {
            $resolvedParam = $this->get($paramName);
        } elseif ($type && !$type->isBuiltin() && $this->has($type->getName())) {
            $resolvedParam = $this->get($type->getName());
        } elseif (($type && $this->isScalarType($type)) && $param->isDefaultValueAvailable()) {
            $resolvedParam = $param->getDefaultValue();
        }
        if (!$resolvedParam && ($type && !$this->isScalarType($type))) {
            $resolvedParam = $this->make($type->getName(), $params, true);
        }
        if (!$resolvedParam && $param->isDefaultValueAvailable()) {
            $resolvedParam = $param->getDefaultValue();
        }
        return $resolvedParam;
    }

    /**
     * Revolve an array of reflected parameters
     *
     * @param array|ReflectionParameter[] $reflectedParameters
     * @param array $params
     * @return array
     * @throws ReflectionException The reflection feature did not work correctly
     */
    protected function resolveParameters(array $reflectedParameters, array $params = []): array
    {
        $resolved = [];
        foreach ($reflectedParameters as $param) {
            $resolved[] = $this->resolveParameter($param, $params);
        }
        return $resolved;
    }

    /**
     * Check whether given type is scalar
     *
     * @param ReflectionNamedType $type
     * @return bool
     */
    private function isScalarType(ReflectionNamedType $type): bool
    {
        return in_array($type->getName(), static::SCALAR_TYPES);
    }
}
