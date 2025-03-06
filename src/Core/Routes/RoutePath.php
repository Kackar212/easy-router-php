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

  // private function parsePath() {
  //   $this->encodedExpressions = $this->getRegularExpressions($this->path);
  //   $routePath = $this->removeRegularExpressions();

  //   $parameters = $this->getParametersNames($routePath);

  //   foreach ($parameters as $index => $param) {
  //     if ($index && !str_starts_with($param, '?') && str_starts_with($parameters[$index - 1], '?')) {
  //       throw new Error("You can't have not optional parameter after optional");
  //     }
  //   }

  //   $routePath = $this->removeParameters($parameters, $routePath);

  //   $segments = $this->url->getSegments($routePath);
  //   $this->routeSegments = [];

  //   foreach ($segments as $segment) {
  //     $this->routeSegments[] = $this->url->trimSegments(explode('::', $segment));
  //   }

  //   if ($this->hasGluedParameters()) {
  //     throw new \Error("You cant have glued parameters!");
  //   }

  //   array_map(function ($parameterName, $encodedExpression) {
  //     if ($encodedExpression) {
  //       $this->parametersWithExpression[$parameterName] = rawurldecode($encodedExpression);
  //     }
  //   }, $parameters, $this->encodedExpressions);


  //   $this->routeSegmentsWithoutParameters = array_map(function ($routeSegment) {
  //     return array_filter($routeSegment, function ($segment, $index) {
  //       return !str_starts_with($segment, '{') && !str_ends_with($segment, '}');
  //     }, ARRAY_FILTER_USE_BOTH);
  //   }, $this->routeSegments);
  // }

  // private function isParameter($parameter) {
  //   if (!$parameter) return false;

  //   return str_starts_with($parameter, '{') && str_ends_with($parameter, '}');
  // }

  // private function hasGluedParameters() {
  //   $segments = $this->routeSegments;

  //   foreach ($segments as $segment) {
  //     foreach ($segment as $index => $routeSegment) {
  //       if ($index) {
  //         if ($this->isParameter($segment[$index]) && $this->isParameter($segment[$index - 1])) {
  //           return true;
  //         }
  //       }
  //     }
  //   }
  // } 

  // private function getRegularExpressions(string $path)
  // {
  //   preg_match_all('/(?={*.<(.*?)(?=>.*}))/', $path, $requirements);

  //   return array_map('rawurlencode', $requirements[1]);
  // }

  // private function removeRegularExpressions()
  // {
  //   $routePath = $this->path;

  //   foreach ($this->encodedExpressions as $expression) {
  //     $decodedExpression = rawurldecode($expression);

  //     $routePath = str_replace("<{$decodedExpression}>", "", $routePath);
  //   }

  //   return $routePath;
  // }

  // private function getParametersNames(string $routePath)
  // {
  //   preg_match_all('/(?<={)(.*?)(?=})/', $routePath, $parameters);

  //   $this->parameters = $parameters[0];

  //   return $parameters[0];
  // }

  // private function removeParameters(array $parameters, string $routePath): string
  // {
  //   foreach ($parameters as $parameter) {
  //     $routeParam = str_replace("?", "\?", "{{$parameter}}");
  //     $routePath = preg_replace("/{$routeParam}/", "::{$routeParam}::", $routePath);
  //   }

  //   return $routePath;
  // }
}