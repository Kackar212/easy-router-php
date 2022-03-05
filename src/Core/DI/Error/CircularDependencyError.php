<?php

namespace EasyRouter\Core\DI\Error;

use Closure;

class CircularDependencyError extends \Error {
  function __construct($class, $method, $parameter)
  {
    if ($method instanceof Closure) {
      $context = 'Closure';
    } else $context = !$class && is_string($method) ? "{$method}" : "{$class}::{$method}";
    

    $details =  "Container cant resolve dependency \${$parameter->getName()} of {$context}()";

    parent::__construct(
      "{$details}! Circular dependency!"
    );
  }
}
