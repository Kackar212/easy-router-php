<?php

namespace EasyRouter\Core\Facades;

use EasyRouter\Core\DI\Container;

class DI
{
  use Facade;
  
  protected static $className = Container::class;
}
