<?php

namespace EasyRouter\Core;

use ArrayIterator;
use Closure;
use EasyRouter\Core\Facades\ArrayList;
use EasyRouter\Core\Facades\URL;
use IteratorAggregate;

class Request implements IteratorAggregate
{
  private array $request;
  private array $headers;
  private array $routeParams = [];
  private array $queryParams = [];

  function __construct()
  {
    $this->queryParams = URL::query(false);
    $this->request = $this->getRequest();
    $this->headers = $this->getHeaders();
  }

  function getIterator(): ArrayIterator
  {
    return new ArrayIterator($this->request);
  }

  private function getRequest()
  {
    return [];
  }

  protected function filterHeaderName($headerName) {
    return str_starts_with($headerName, 'HTTP_');
  }

  protected function mapHeaderName($headerName) {
    return [str_replace('_', '-', substr($headerName, 5)), $headerName];
  }

  private function getHeadersFromGlobal()
  {
    $headers = ArrayList::getKeys($_SERVER)
      ->filter(Closure::fromCallable([$this, 'filterHeaderName']))
      ->map(Closure::fromCallable([$this, 'mapHeaderName']))
      ->map(function ($headerName) {
        [$parsed, $original] = $headerName;

        return [$parsed, $_SERVER[$original]];
      })
      ->fromEntries();


    return (array) $headers;
  }

  private function getHeaders(): array
  {
    $headers = [];

    if (function_exists('getallheaders')) {
      $headers = getallheaders();
    } else {
      $headers = $this->getHeadersFromGlobal();
    }

    return array_change_key_case($headers, CASE_LOWER);
  }

  function setRouteParameters($routeParams) {
    $this->routeParams = $routeParams;
  }

  function method() {
    return $_SERVER['REQUEST_METHOD'];
  }

  function has(string $key, array $data = null) {
    $data = $data ?? $this->request;
    if ($key) {
      $key = strtolower($key);
    }

    return $key && isset($data[$key]);
  }

  function hasHeader(string $header) {
    return $this->has($header, $this->headers);
  }

  function hasQueryParameter(string $queryParameter) {
    return $this->has($queryParameter, $this->queryParams);
  }

  function hasRouteParameter(string $routeParameter) {
    return $this->has($routeParameter, $this->routeParams);
  }

  function headers(string $header = null): array|string|null
  {
    $header = strtolower($header);

    if ($header && !$this->hasHeader($header)) {
      return null;
    }

    return $header ? $this->headers[$header] : $this->headers;
  }

  function get(string $key = null)
  {
  }

  function queryParams(string $key = null): string|array|null
  {
    if ($key && !$this->hasQueryParameter($key)) {
      return null;
    }

    return $key ? $this->queryParams[$key] : $this->queryParams;
  }

  function routeParams(string $key = null): string|array|null
  {
    if ($key && !$this->hasRouteParameter($key)) {
      return null;
    }

    return $key ? $this->routeParams[$key] : $this->routeParams;
  }
}
