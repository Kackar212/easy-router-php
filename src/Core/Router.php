<?php

namespace EasyRouter\Core;

use Closure;
use EasyRouter\Core\Facades\DI;
use EasyRouter\Core\Facades\URL;
use EasyRouter\Core\Routes\Routes;

class Router
{
  private Routes $routes;
  private object $settings;
  private $component;
  private string $namespace = '';
  private Request $request;

  function __construct(Routes $routes, Request $request)
  {
    $this->routes = $routes;
    $this->request = $request;
    $this->settings = (object) ["response" => "text"];
  }

  function setControllersNamespace(string $namespace) {
    $this->namespace = $namespace;
  }

  function get(string $path, Closure|string|array $component, array $options = [])
  {
    $this->add($path, $component, [...$options, "method" => "GET"]);
    
    return $this;
  }

  function post(string $path, Closure|string|array $component, array $options = [])
  {
    $this->add($path, $component, [...$options, "method" => "POST"]);
    
    return $this;
  }

  function delete(string $path, Closure|string|array $component, array $options = [])
  {
    $this->add($path, $component, [...$options, "method" => "DELETE"]);
    
    return $this;
  }

  function put(string $path, Closure|string|array $component, array $options = [])
  {
    $this->add($path, $component, [...$options, "method" => "PUT"]);
    
    return $this;
  }

  function add(string $path, Closure|string|array $component, $options = []): void
  {
    $this->component = $component;
    
    if ($this->isComponent($component)) {
      $this->parseComponent($component);
    }

    $this->routes->add([$path => [$this->component, $options]]);
  }

  function fallback($component) {
    $this->add("", $component, []);

    return $this;
  }

  function changeSettings (array $newSettings) {
    return $this->settings = (object) array_merge((array) $this->settings, $newSettings);
  }

  function start() {
    $matchedRoutes = $this->routes->get(URL::path());

    if ($matchedRoutes->isEmpty()) {
      return $this->pageNotFound();
    }

    $routesWithRequestMethod = $matchedRoutes->filter(function ($route) {
      return $route->isMethodAllowed($this->request->method());
    });

    if ($routesWithRequestMethod->isEmpty()) {
      return $this->methodNotAllowed();
    }

    $matchedRoute = $routesWithRequestMethod->first();

    $component = $matchedRoute->getComponent();

    $this->request->setRouteParameters($matchedRoute->getParameters());

    DI::instances([Request::class => $this->request]);

    $this->run($component);
  }

  private function run($component) {
    if (is_array($component)) {
      [$controller, $method] = $component;

      DI::call(
        DI::resolve($controller),
        $method
      );
    } else {
      DI::callFunction($component);
    }
  }

  private function methodNotAllowed() {
    http_response_code(405);

    switch ($this->settings->response) {
      case 'json': {
        echo json_encode(["message" => "Method not allowed", "code" => 405]);
        return;
      }
      case 'text': {
        echo "405 - Method not allowed";
        return;
      }
    }
  }

  private function pageNotFound() {
    http_response_code(404);

    switch ($this->settings->response) {
      case 'text': {
        echo "404 - Page Not Found";
        break;
      }
      case 'json': {
        echo json_encode(["message" => "Page Not Found", "code" => 404]);
      }
    }
  }

  private function isComponent($component) {
    if (!is_string($component)) {
      return false;
    }

    return str_contains($component, '@');
  }

  private function parseComponent($component) {
    [$controller, $method] = explode('@', $component);
    
    $controller = $this->namespace . $controller;

    $this->component = [$controller, $method];
  }
}
