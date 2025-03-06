<?php

namespace EasyRouter\Shared;

use ArrayAccess;
use IteratorAggregate;

class TypedArray implements ArrayAccess, IteratorAggregate
{
  use Collection {
    Collection::offsetSet as set;
  }

  private string $type;

  function __construct(string $type = 'all', array $storage = [])
  {
    $this->storage = &$storage;
    $this->type = $type;
  }

  private function parseType($type)
  {
    switch ($type) {
      case 'double':
        return 'float';
      case 'integer':
        return 'int';
      default:
        return $type;
    }
  }

  private function checkType($value)
  {
    if ($this->type === 'all')
      return true;

    $valueType = null;
    if (is_object($value) && $this->type !== 'object') {
      $valueType = get_class($value);
    } else {
      $valueType = gettype($value);
    }

    $valueType = $this->parseType($valueType);

    if ($valueType !== $this->type) {
      throw new \Error("You cant append {$valueType} to Array<{$this->type}>");
    }
  }

  function offsetSet(mixed $key, mixed $value): void
  {
    $this->checkType($value);

    $this->set($key, $value);
  }

  function getDeep()
  {
    return $this->deep($this->storage, ["type" => $this->type]);
  }
}
