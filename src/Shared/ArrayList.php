<?php

namespace EasyRouter\Shared;

use ArrayAccess;
use IteratorAggregate;

class ArrayList implements ArrayAccess, IteratorAggregate {
  use Collection;

  function __construct(array $storage = [])
  {
    $this->storage = $storage;
  }
}
