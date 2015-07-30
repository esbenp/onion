<?php

use Optimus\Onion\LayerInterface;
use Optimus\Onion\Onion;

class BeforeLayer implements LayerInterface {

    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function peel($object, Closure $next)
    {
        $object->runs[] = $this->id;

        return $next($object);
    }

}

class AfterLayer implements LayerInterface {

    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function peel($object, Closure $next)
    {
        $response = $next($object);

        $object->runs[] = $this->id;

        return $response;
    }

}

class OnionTest extends Orchestra\Testbench\TestCase {

    public function testLayersAreRunInCorrectOrder()
    {
        $object = new StdClass;
        $object->runs = [];

        $onion = new Optimus\Onion\Onion();

        $end = $onion
            ->layer(new BeforeLayer(1))
            ->layer(new AfterLayer(4))
            ->layer(new BeforeLayer(3))
            ->layer(new AfterLayer(2))
            ->peel($object, function($object){
                $object->runs[] = 'core';

                return $object;
            });

        $this->assertEquals([
            1, 3, 'core', 2, 4
        ], $end->runs);
    }

    public function testAddingAnOnionAndArrayWorks()
    {
        $object = new StdClass;
        $object->runs = [];

        $onion1 = new Optimus\Onion\Onion();
        $onion1 = $onion1->layer([
            new BeforeLayer(1),
            new AfterLayer(4)
        ]);

        $onion2 = new Optimus\Onion\Onion([
            new BeforeLayer(3),
            new AfterLayer(2)
        ]);

        $end = $onion1->layer($onion2)
                      ->peel($object, function($object){
                          $object->runs[] = 'core';

                          return $object;
                      });

        $this->assertEquals([
            1, 3, 'core', 2, 4
        ], $end->runs);
    }

}
