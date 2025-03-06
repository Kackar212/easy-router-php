<?php

namespace EasyRouter\Core\DI;

use Closure;
use EasyRouter\Core\DI\Error\CircularDependencyError;
use EasyRouter\Core\DI\Error\NotInstantiableError;
use EasyRouter\Core\DI\Error\PrimitiveTypeError;
use Reflection;
use ReflectionClass;
use ReflectionParameter;

class Container
{
  private array $buildStack = [];
  private array $resolveWith = [];
  private array $instances = [];

  private function isPrimitive(ReflectionParameter $parameter)
  {
    return $parameter->hasType() && $parameter->getType()->isBuiltin() || !$parameter->hasType();
  }

  private function getParameterClass(ReflectionParameter $parameter)
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

  private function resolvePrimitive(ReflectionParameter $parameter, ReflectionClass|string $reflection = null)
  {
    $parameterName = $parameter->getName();
    $name = is_string($reflection) ? $reflection : $reflection->getName();

    if (array_key_exists($parameterName, $this->resolveWith[$name])) {
      $value = $this->resolveWith[$name][$parameterName];

      unset($this->resolveWith[$name][$parameterName]);

      if (count($this->resolveWith[$name]) === 0) {
        unset($this->resolveWith[$name]);
      }

      return $value;
    }

    if ($parameter->isOptional()) {
      return $parameter->getDefaultValue();
    }

    $class = array_pop($this->buildStack);
    if ($reflection instanceof ReflectionClass) {
      $class = $class ?? $reflection ? $reflection->getName() : '';
    }

    throw new PrimitiveTypeError($class, $parameter->getDeclaringFunction()->getName(), $parameter);
  }

  public function resolve(Closure|string|array $abstract, array $with = [], array $resolved = [])
  {
    $serializedAbstract = is_array($abstract) ? json_encode($abstract) : $abstract;

    if (is_callable($abstract)) {
      $tempName = "function_" . random_bytes(16);
      $this->resolveWith[$tempName] = $with;

      $reflectionFunction = new \ReflectionFunction($abstract);

      $resolved = $this->getArgs($reflectionFunction->getParameters(), $with, $tempName);

      return $reflectionFunction->invokeArgs($resolved);
    }

    $this->resolveWith[$serializedAbstract] = $with;

    if (!is_callable($abstract) && is_array($abstract)) {

      [$instance, $method] = $abstract;


      $reflectionMethd = new \ReflectionMethod($instance, $method);
      $resolvedInstance = $this->resolve($instance);
      $args = $this->getArgs($reflectionMethd->getParameters(), $with, json_encode($abstract));

      return $resolvedInstance->{$method}(...$args);
    }

    if (is_callable($abstract) && is_array($abstract)) {
      [$instance, $method] = $abstract;

      $reflectionMethd = new \ReflectionMethod($instance, $method);
      $args = $this->getArgs($reflectionMethd->getParameters(), $with, json_encode($abstract));

      return $instance->{$method}(...$args);
    }

    $reflection = new ReflectionClass($abstract);
    $constructor = $reflection->getConstructor();

    if (!$reflection->isInstantiable()) {
      throw new NotInstantiableError($reflection->getName());
    }

    $parameters = $constructor ? $constructor->getParameters() : [];
    $hasDependencies = count($parameters) > 0;

    if (!$hasDependencies) {
      return $reflection->newInstance();
    }

    $resolved = $this->getArgs($parameters, $with, $reflection);

    return $reflection->newInstanceArgs($resolved);
  }

  public function getArgs(array $parameters, array $with, ReflectionClass|string $reflection = null)
  {
    $resolved = [];

    foreach ($parameters as $parameter) {
      if ($this->isPrimitive($parameter)) {
        $resolved[] = $this->resolvePrimitive($parameter, $reflection);

        continue;
      }

      $parameterClass = $this->getParameterClass($parameter);

      if ($parameter->isOptional()) {
        $resolved[] = $parameter->getDefaultValue();

        continue;
      }

      if (in_array($parameterClass, $this->buildStack)) {
        throw new CircularDependencyError($parameterClass, "", $parameter);
      }

      $this->buildStack[] = $parameterClass;

      if (isset($this->instances[$parameterClass])) {
        $resolved[] = $this->instances[$parameterClass];

        continue;
      }

      $resolved[] = $this->resolve($parameterClass, $with, $resolved);

      array_pop($this->buildStack);
    }

    return $resolved;
  }

  public function instances(array $instances)
  {
    $this->instances = array_merge($this->instances, $instances);
  }
}
