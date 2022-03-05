<?php

namespace EasyRouter\Core\DI\Error;

class NotInstantiableError extends \Error {
  function __construct($notInstantiable)
  {
    parent::__construct(
      "{$notInstantiable} is not instantiable!"
    );
  }
}
