<?php

namespace EasyRouter\Core\Routes;

use EasyRouter\Core\Request;
use EasyRouter\Utils\URL;

class Route extends RoutePath
{
  public object $options;
  private $component;

  function __construct(URL $url, Request $request, string $path, $component, array $options = [])
  {
    parent::__construct($url, $path);
    $this->request = $request;
    $this->options = RouteOptions::merge($options);
    $this->component = $component;
  }

  private function getParamsValues($urlPathSegments): array {
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

      if (empty($copy)) $params[] = $urlPathSegment;
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

  function isMethodAllowed(string $method): bool {
    return $this->options->method === $method;
  }

  function getComponent()
  {
    return $this->component;
  }

  function getParameters() {
    return $this->parameters;
  }

  function matchPath(string $path): bool
  {
    if ($this->path === $path) return true;

    $path = str_ends_with($path, '/') ? substr($path, 0, strlen($path) - 1) : $path;

    $urlPathSegments = $this->url->getSegments($path);

    $params = $this->getParamsValues($urlPathSegments);

    if (count($this->parameters) !== count($params)) {
      return false;
    }

    $this->parameters = array_combine($this->parameters, $params);

    foreach ($this->parameters as $parameterName => $parameter) {
      $regex = $this->parametersWithExpression[$parameterName] ?? '(.+)';

      $isOptional = str_starts_with($parameterName, '?');

      if ($isOptional) {
        $this->parameters[substr($parameterName, 1)] = $parameter;
      }

      if ($parameter === "" && $isOptional) {
        continue;
      }

      preg_match("/{$regex}/", $parameter, $match);

      if (!isset($match[0]) || $parameter !== $match[0]) {
        return false;
      }
    }

    return true;
  }

  function isPathEmpty()
  {
    return $this->path === '';
  }
}
