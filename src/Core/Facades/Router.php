<?php

namespace EasyRouter\Core\Facades;

use EasyRouter\Core\Request;
use EasyRouter\Core\Router as RouterService;
use EasyRouter\Core\Routes\Routes;

class Router
{
  use Facade;
  
  protected static function getInstance() {
    $args = [DI::resolve(Routes::class), DI::resolve(Request::class)];
    
    self::$instance = new RouterService(...$args);
  }
}
