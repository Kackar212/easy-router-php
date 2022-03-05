<?php

namespace EasyRouter\Core\DI\Error;

class PrimitiveTypeError extends \Error {
  function __construct($class, $method, $parameter)
  {
    $context = !$class && is_string($method) ? "{$method}" : "{$class}::{$method}";
    $parameterType = $parameter->hasType() ? $parameter->getType()->getName() : '';

    $details =  "Container can't resolve dependency {$parameterType} \${$parameter->getName()} of {$context}()";

    parent::__construct(
      "{$details}! Default value is not available and type is primitive, you didn't specify type or this class(?) {$parameterType} doesn't exists"
    );
  }
}
