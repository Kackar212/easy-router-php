<?php

namespace EasyRouter\Core\Routes;

use CurlHandle;
use EasyRouter\Core\Tokenizer\Token;
use EasyRouter\Core\Tokenizer\Tokenizer;
use EasyRouter\Utils\URL;
use Error;

class RoutePath
{
  protected URL $url;
  protected Tokenizer $tokenizer;

  protected string $path;
  protected array $encodedExpressions = [];
  protected array $parametersWithExpression = [];
  protected array $routeSegmentsWithoutParameters = [];
  protected array $parameters;
  protected array $routeSegments;
  protected string $routeRegexp = "";
  protected array $tokens = [];

  function __construct(URL $url, Tokenizer $tokenizer, $path)
  {
    $this->url = $url;
    $this->tokenizer = $tokenizer;

    $this->path = $path !== '/' && str_starts_with($path, '/') ? substr($path, 1) : $path;
  }

  protected function getRouteRegexp()
  {
    if ($this->routeRegexp !== "") {
      return $this->routeRegexp;
    }

    $this->tokens = $this->tokenizer->tokenize($this->path);

    $result = '';
    foreach ($this->tokens as $token) {
      if ($token->type === Token::STATIC ) {
        $result .= preg_quote(rawurlencode($token->value));

        continue;
      }

      if ($token->regexp === '') {
        $token->regexp = '.+';
      }

      $result .= "(?<{$token->value}>({$token->regexp})";
      if ($token->isOptional) {
        $result .= "?";
      }
      $result .= ")";
    }

    $this->routeRegexp = $result;

    return $result;
  }

  public function getPath()
  {
    return $this->path;
  }
}