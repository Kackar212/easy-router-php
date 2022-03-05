<?php

namespace EasyRouter\Core\DI;

use Closure;
use EasyRouter\Core\DI\Error\CircularDependencyError;
use EasyRouter\Core\DI\Error\NotInstantiableError;
use EasyRouter\Core\DI\Error\PrimitiveTypeError;
use EasyRouter\Shared\TypedArray;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionParameter;

class Container
{
  private array $with = [];
  private array $instances = [];
  private array $resolved = [];
  private array $resolveWith = [];
  private ?string $functionName = null;

  function __construct(array $with = [], array $instances = [])
  {
    $this->with = $with;
    $this->instances = $instances;
    $this->buildStack = [];
  }

  private function hasDependencies(?ReflectionFunctionAbstract $reflection): bool
  {
    if (!$reflection) return false;

    $parameters = $reflection->getParameters();

    return !!count($parameters);
  }

  private function isInstantiable(ReflectionClass $reflection): bool
  {
    $abstract = $reflection->getName();

    if (!$reflection->isInstantiable()) {
      throw new NotInstantiableError($abstract);
    }

    return true;
  }

  private function getClass(ReflectionParameter $parameter)
  {
    $hasType = $parameter->hasType();

    if (!$hasType) {
      return false;
    }

    $type = $parameter->getType()->getName();

    if (class_exists($type)) {
      return $type;
    }

    return false;
  }

  private function resolvePrimitive(ReflectionParameter $parameter, ?ReflectionClass $reflection)
  {
    if ($reflection || $this->functionName) {
      $name = $this->functionName ? $this->functionName : $reflection->getName();
      $staticParameters =  &$this->resolveWith[$name] ?? false;

      $parameterName = $parameter->getName();
      if ($staticParameters && isset($staticParameters[$parameterName])) {
        return $staticParameters[$parameterName];
      }
    }

    if ($parameter->isOptional()) {
      return $parameter->getDefaultValue();
    }

    $class = array_pop($this->buildStack);
    $class = $class ?? $reflection ? $reflection->getName() : '';

    throw new PrimitiveTypeError($class, $parameter->getDeclaringFunction()->getName(), $parameter);
  }

  private function resolveClass($class, $method)
  {
    $resolvedClass = $this->resolve($class);

    array_pop($this->buildStack);

    return $resolvedClass;
  }

  private function getArgs(ReflectionFunctionAbstract $method, ?ReflectionClass $classReflection = null)
  {
    $parameters = $method->getParameters();
    $resolvedParameters = [];

    foreach ($parameters as $parameter) {
      $class = $this->getClass($parameter);

      if (!$class) {
        $resolvedParameters[] = $this->resolvePrimitive($parameter, $classReflection);
        continue;
      }

      if (array_search($class, $this->buildStack)) {
        throw new CircularDependencyError($class, $method->getName(), $parameter);
      }

      $this->buildStack[] = $class;
      $resolvedParameters[] = $this->resolveClass($class, $method);
    }

    return $resolvedParameters;
  }

  function resolve(Closure|string $abstract, array $with = null)
  {
    $reflection = new ReflectionClass($abstract);
    $args = [];

    if ($this->isInstantiable($reflection)) {
      if (!isset($this->resolved[$abstract])) {
        $this->resolved[$abstract] = new TypedArray(\object::class);
      }

      if (isset($this->instances[$abstract])) {
        return $this->instances[$abstract];
      }

      $this->resolveWith[$abstract] = $with ?? $this->with[$abstract] ?? [];
      $this->buildStack[] = $abstract;

      $constructor = $reflection->getConstructor();
      if ($this->hasDependencies($constructor)) {
        $args = $this->getArgs($constructor, $reflection);
      }

      array_pop($this->buildStack);
      return $this->resolved[$abstract][] = $reflection->newInstanceArgs($args);
    }
  }

  function call(object $instance, string $method, array $with = null)
  {
    $reflection = new ReflectionClass($instance);
    $args = [];

    $this->functionName = $method;
    $this->resolveWith[$this->functionName] = $with;

    $reflectionMethod = $reflection->getMethod($method);

    if ($this->hasDependencies($reflectionMethod)) {
      $args = $this->getArgs($reflectionMethod, $reflection);
    }

    $this->functionName = null;
    return $instance->{$method}(...$args);
  }

  function callFunction(Closure|string $func, array $with = [])
  {
    $function = new ReflectionFunction($func);
    $args = [];

    $this->functionName = $function->getName() . random_bytes(10);
    $this->resolveWith[$this->functionName] = $with;

    if ($this->hasDependencies($function)) {
      $args = $this->getArgs($function);
    }

    $this->functionName = null;
    return $function->invokeArgs($args);
  }

  function with(array $with): Container
  {
    $this->with = array_merge($this->with, $with);

    return $this;
  }

  function instances(array $instances): Container
  {
    $this->instances = array_merge($this->instances, $instances);

    return $this;
  }

  function get(string $className): TypedArray
  {
    return $this->resolved[$className];
  }
}
