<?php

namespace EasyRouter\Core\Facades;

use EasyRouter\Shared\ArrayList as ArrayListService;

class ArrayList
{
  use Facade;
  
  protected static $className = ArrayListService::class;
}
