# Onion

[![Build Status](https://travis-ci.org/esbenp/onion.svg)](https://travis-ci.org/esbenp/onion) [![Coverage Status](https://coveralls.io/repos/esbenp/onion/badge.svg?branch=master&service=github)](https://coveralls.io/github/esbenp/onion?branch=master)

A standalone middleware library without dependencies inspired by middleware in Laravel (Illuminate/Pipeline).

I have written a small blog post about the library and how to use it:
[Implementing before/after middleware in PHP](http://esbenp.github.io/2015/07/31/implementing-before-after-middleware/)

## Installation

```bash
composer require optimus/onion 0.1.*
```

## Usage

```php
class BeforeLayer implements LayerInterface {

    public function peel($object, Closure $next)
    {
        $object->runs[] = 'before';

        return $next($object);
    }

}

class AfterLayer implements LayerInterface {

    public function peel($object, Closure $next)
    {
        $response = $next($object);

        $object->runs[] = 'after';

        return $response;
    }

}

$object = new StdClass;
$object->runs = [];

$onion = new Onion;
$end = $onion->layer([
                new AfterLayer(),
                new BeforeLayer(),
                new AfterLayer(),
                new BeforeLayer()
            ])
            ->peel($object, function($object){
                $object->runs[] = 'core';
                return $object;
            });

var_dump($end);
```

Will output

```
..object(stdClass)#161 (1) {
  ["runs"]=>
  array(5) {
    [0]=>
    string(6) "before"
    [1]=>
    string(6) "before"
    [2]=>
    string(4) "core"
    [3]=>
    string(5) "after"
    [4]=>
    string(5) "after"
  }
}
```
