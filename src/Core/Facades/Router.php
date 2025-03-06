<?php

namespace EasyRouter\Core\Facades;

use EasyRouter\Core\Request;
use EasyRouter\Core\Router as RouterService;
use EasyRouter\Core\Routes\Routes;

class Router
{
  use Facade;
  
  protected static function getInstance() {
    self::$instance = DI::resolve(RouterService::class);
  }
}
