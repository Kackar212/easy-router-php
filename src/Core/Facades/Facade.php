<?php

namespace EasyRouter\Core\Facades;

trait Facade
{
  protected static $instance;

  private function __construct()
  {
  }

  protected static function getInstance()
  {
    $args = isset(self::$args) ? self::$args : [];

    self::$instance = new self::$className(...$args);
  }

  static function __callStatic(string $name, array $arguments)
  {

    if (!self::$instance) {
      self::getInstance();
    }

    return self::$instance->{$name}(...$arguments);
  }
}
