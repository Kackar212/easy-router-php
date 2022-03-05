<?php

namespace EasyRouter\Utils;

class URL
{
  private array $parsedURL;
  private string $current;
  private array|object $query;

  function __construct()
  {
    $this->current = $this->current();
    $this->parsedURL = parse_url($this->current);
    $this->query = isset($this->parsedURL['query']) ? $this->parseQuery() : [];
  }

  private function parseQuery()
  {
    $params = $this->parseParams($this->parsedURL['query']);

    return $params;
  }

  private function hasValue(string $param) {
    return str_contains($param, '=');
  }

  function query($asObject = true)
  {
    return $asObject ? (object)$this->query : $this->query;
  }

  function parseParams(string $query): array
  {
    $paramsAsArray = explode('&', $query);
    $parsedParams = [];

    foreach ($paramsAsArray as $param) {
      if (!$this->hasValue($param)) {
        $param = $param . '=';
      }

      [$paramName, $paramValue] = explode('=', $param);
      $parsedParams[$paramName] = $paramValue;
    }

    return $parsedParams;
  }

  function createQuery(array $query)
  {
    http_build_query($this->query);
  }

  function removeParams(string ...$params)
  {
    foreach ($params as $param) {
      unset($this->query[$param]);
    }
  }

  function getParam(string $param)
  {
    return $this->query[$param];
  }

  function hasParams(string ...$params): bool
  {
    if (count($params) === 1) {
      return isset($this->query[$params]);
    }

    foreach ($params as $param) {
      if (!isset($this->query[$param])) {
        return false;
      }
    }

    return true;
  }

  function path(): string
  {
    $path = str_replace($this->root(), '', $this->parsedURL['path']);

    if ($path === '/') {
      return $path;
    }

    return str_starts_with($path, '/') ? substr($path, 1) : $path;
  }

  function fragment(): string
  {
    return $this->parsedURL['fragment'] ?? '';
  }

  function current()
  {
    return $_SERVER['REQUEST_URI'];
  }

  function trimSegments(array $segments): array {
    foreach ($segments as $index => $segment) {
      if (!$segment) unset($segments[$index]);
    }

    return array_values($segments);
  }

  function getSegments($path = null, $clearWhitespaces = true): array
  {
    $segments = explode('/', $path ?? $this->path());

    return $segments;
  }

  function root(): string
  {
    $path = $_SERVER['PHP_SELF'];
    $segments = $this->getSegments($path, false);

    $rootSegments = array_filter($segments, function ($segment) {
      return $segment !== 'public' && $segment !== 'index.php';
    });

    return implode('/', $rootSegments);
  }
}
