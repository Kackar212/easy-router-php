<?php

namespace EasyRouter\Core\Routes;

class RouteOptions {
  private static $defaultOptions = [
    "method" => "GET",
  ];

  static function merge(array $options) {
    return (object) array_merge(self::$defaultOptions, $options);
  }
}
