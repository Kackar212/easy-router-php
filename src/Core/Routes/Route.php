<?php

namespace EasyRouter\Core\Routes;

use EasyRouter\Core\Facades\URL as FacadesURL;
use EasyRouter\Core\Request;
use EasyRouter\Core\Tokenizer\Token;
use EasyRouter\Core\Tokenizer\Tokenizer;
use EasyRouter\Utils\URL;

class Route extends RoutePath
{
  public object $options;

  private $component;
  private array $matchResult = [];

  private Request $request;
  protected Tokenizer $tokenizer;

  function __construct(URL $url, Request $request, Tokenizer $tokenizer, string $path, $component, array $options = [])
  {
    parent::__construct($url, $tokenizer, $path);

    $this->request = $request;
    $this->options = RouteOptions::merge($options);
    $this->component = $component;
    $this->tokenizer = $tokenizer;
  }

  private function isMatch()
  {
    return count($this->matchResult) > 0;
  }

  private function getParamsValues($urlPathSegments): array
  {
    $params = [];

    foreach ($urlPathSegments as $index => $urlPathSegment) {
      $copy = $this->routeSegmentsWithoutParameters[$index] ?? [];

      foreach ($copy as $routeSegmentIndex => $segment) {

        $paramsFromUrlSegment = explode($segment, $urlPathSegment);

        if ($segment === $urlPathSegment) {
          $paramsFromUrlSegment = [];
        }

        if ($routeSegmentIndex === 0) {
          array_shift($paramsFromUrlSegment);
        }

        $hasPathSegment = array_search($urlPathSegment, $paramsFromUrlSegment);

        if ($hasPathSegment === false) {
          $params = [...$params, ...$paramsFromUrlSegment];
        }
      }

      if (empty($copy))
        $params[] = $urlPathSegment;
    }



    if (count($this->routeSegments) !== count($urlPathSegments)) {
      $routeSegments = array_merge(...$this->routeSegments);

      foreach ($routeSegments as $index => $segment) {
        if (!isset($params[$index]) && isset($this->parameters[$index]) && str_starts_with($this->parameters[$index], '?')) {
          $params[$index] = "";
        }
      }
    }

    return $params;
  }

  function isMethodAllowed(string $method): bool
  {
    return $this->options->method === $method;
  }

  function getComponent()
  {
    return $this->component;
  }

  function getParameters()
  {
    if (!$this->isMatch())
      return [];

    $parameters = array_filter($this->tokens, function ($token) {
      return $token->type === Token::PARAMETER;
    });

    return array_reduce($parameters, function ($result, $token) {
      $result[$token->value] = $this->matchResult[$token->value];

      return $result;
    }, []);
  }


  function matchPath(string $path): bool
  {
    if ($this->path === "*") {
      return true;
    }

    $routeRegexp = $this->getRouteRegexp();

    preg_match("/^{$routeRegexp}$/", $path, $this->matchResult);

    return $this->isMatch();
  }

  function isPathEmpty()
  {
    return $this->path === '';
  }
}
