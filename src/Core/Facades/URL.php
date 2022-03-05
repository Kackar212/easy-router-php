<?php

namespace EasyRouter\Core\Facades;

use EasyRouter\Utils\URL as URLService;

class URL
{
  use Facade;

  protected static $className = URLService::class;
}
