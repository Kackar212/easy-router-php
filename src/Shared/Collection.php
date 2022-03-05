<?php

namespace EasyRouter\Shared;

use ArrayIterator;
use EasyRouter\Core\Facades\DI;

trait Collection {
  private array $storage;

  function getIterator(): ArrayIterator
  {
    return new ArrayIterator($this->storage);
  }

  function deep(array $storage, array $args) {
    foreach ($storage as $index => $value) {
      if (is_array($value)) {
        $storage[$index] = $this->deep($value, $args);
      }
    }

    return DI::resolve($this::class, array_merge($args, ["storage" => $storage]));
  }

  function length() {
    return count($this->storage);
  }

  function getLastIndex(): int {
    $length = $this->length();
    return $length ? $length - 1 : $length;
  }

  function last()
  {
    $lastIndex = array_key_last($this->storage);

    if (!$lastIndex && $lastIndex !== 0) {
      return null;
    }

    return $this->storage[$lastIndex];
  }

  function first()
  {
    $index = array_key_first($this->storage);

    if (!$index && $index !== 0) {
      return null;
    }

    return $this->storage[$index];
  }

  function find(callable $callback) {
    foreach ($this->storage as $value) {
      if (call_user_func($callback, $value)) {
        return $value;
      }
    }

    return null;
  }

  function offsetExists(mixed $offset): bool
  {
    return isset($this->storage[$offset]);
  }

  function offsetSet(mixed $key, mixed $value): void
  {
    if (!$key) {
      $key = $this->length();
    }

    $this->storage[$key] = $value;
  }

  function &offsetGet($offset) {
    if ($this->offsetExists($offset)) {
      return $this->storage[$offset];
    }
  }

  function offsetUnset(mixed $offset): void
  {
    if ($this->offsetExists($offset)) {
      unset($this->storage[$offset]);
    }
  }

  function getKeys(array|object $objectOrArray = null): self {
    $result = [];

    if (!$objectOrArray) {
      $result = array_keys($this->storage);
    } else if (is_array($objectOrArray)) {
      $result = array_keys($objectOrArray);
    } else {
      $result = array_keys(get_object_vars($objectOrArray));
    }

    return $this->create($result);
  }

  function getValues(array|object $objectOrArray = null): self {
    $result = [];

    if (!$objectOrArray) {
      $result = array_values($this->storage);
    } else if (is_array($objectOrArray)) {
      $result = array_values($objectOrArray);
    } else {
      $result = array_values(get_object_vars($objectOrArray));
    }

    return $this->create($result);
  }

  function filter(callable $callback, int $mode = 0): self {
    $storage = $this->toArray();

    $result = array_filter($storage, $callback, $mode);

    return $this->create($result);
  }

  function isEmpty() {
    return empty($this->storage);
  }

  function map(callable $callback, array ...$arrays) {
    $result = array_map($callback, $this->toArray(), ...$arrays);
    
    return $this->create($result);
  }

  function fromEntries(array|Collection $entries = null) {
    $entries = $entries ?? $this->toArray();
    if ($entries instanceof Collection) {
      $entries = $entries->toArray();
    }

    $result = [];

    foreach ($entries as [$key, $value]) {
      $result[$key] = $value;
    }

    return (object) $result;
  }

  function toArray(): array {
    return $this->storage;
  }

  function toObject(): object {
    return (object) $this->storage;
  }

  function create(array $storage = null): self {
    return DI::resolve($this::class, array_merge(get_object_vars($this), ["storage" => $storage ?? $this->storage]));
  }
}
