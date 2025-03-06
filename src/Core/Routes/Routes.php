<?php

namespace EasyRouter\Core\Routes;

use EasyRouter\Core\Facades\DI;
use EasyRouter\Shared\TypedArray;
use EasyRouter\Utils\URL;

class Routes extends TypedArray {
  private URL $url;
  public $f;

  function __construct(URL $url, array $storage = []) {
    parent::__construct(Route::class, $storage);
    
    $this->url = $url;
  }

  function add(array $routeData) {
    $path = array_key_first($routeData);
    [$component, $options] = $routeData[$path];

    $route = $this->find(function (Route $route) use ($path) {
      $route->getPath() === $path;
    });

    if ($route) {
      $route->addMethod($options["method"]);

      return;
    }

    $this[] = DI::resolve(Route::class, ["path" => $path, "component" => $component, "options" => $options]);
  }

  function get(string $path) {
    $matchedRoutes = $this->filter(function (Route $route) use ($path) {
      return $route->matchPath($path);
    });

    return $matchedRoutes;
  }
}
