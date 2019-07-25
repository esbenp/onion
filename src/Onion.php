<?php

namespace Optimus\Onion;

use InvalidArgumentException;
use Closure;
use Optimus\Onion\LayerInterface;

class Onion {

    private $layers;

    public function __construct(array $layers = [])
    {
        $this->layers = $layers;
    }

    /**
     * Add layer(s) or Onion
     * @param  mixed $layers
     * @return Onion
     */
    public function layer($layers)
    {
        if ($layers instanceof Onion) {
            $layers = $layers->toArray();
        }

        if ($layers instanceof LayerInterface) {
            $layers = [$layers];
        }

        if (!is_array($layers)) {
            throw new InvalidArgumentException(get_class($layers) . " is not a valid onion layer.");
        }

        return new static(array_merge($this->layers, $layers));
    }

    /**
     * Run middleware around core function and pass an
     * object through it
     * @param  mixed  $object
     * @param  Closure $core
     * @return mixed         
     */
    public function peel($object, Closure $core)
    {
        $coreFunction = $this->createCoreFunction($core);

        // Since we will be "currying" the functions starting with the first
        // in the array, the first function will be "closer" to the core.
        // This also means it will be run last. However, if the reverse the
        // order of the array, the first in the list will be the outer layers.
        $layers = array_reverse($this->layers);

        // We create the onion by starting initially with the core and then
        // gradually wrap it in layers. Each layer will have the next layer "curried"
        // into it and will have the current state (the object) passed to it.
        $completeOnion = array_reduce($layers, function($nextLayer, $layer){
            return $this->createLayer($nextLayer, $layer);
        }, $coreFunction);
        
        /// It is a interesting model. It looks like a recursion, to better 
        /// understand the mechanism, i created a model. So, after making a
        /// array_reduce we have, a something what looks like telescope.
        /// The coreFunction is carryed to te top of the telescope.
        /// Looks closer:
        /// Lets say that fn($nextLayer, $layer) = function($nextLayer, $layer){
        ///    return $this->createLayer($nextLayer, $layer);
        /// }
        /// In the first step we have:
        /// fn($coreFunction, $layer[3]), then
        /// fn(fn($coreFunction, $layer[3]), $layer[2]), then
        /// fn(fn(fn($coreFunction, $layer[3]), $layer[2]), $layer[1]), then
        /// fn(fn(fn(fn($coreFunction, $layer[3]), $layer[2]), $layer[1]), $layer[0])
        /// so it is a function depending on the one agument. This argument is called $object.
        /// fn(fn(fn(fn($coreFunction, $layer[3]), $layer[2]), $layer[1]), $layer[0])($object);
        /// Now, we have to consider what is a fn function.
        /// $layer[0]->peel($object, fn(fn(fn($coreFunction, $layer[3]), $layer[2]), $layer[1]))
        /// Then we have depending from class:
        /// fn(fn(fn($coreFunction, $layer[3]), $layer[2]), $layer[1])($object);
        /// $object->runs[] = 'after';
        /// Then we have:
        /// $object->runs[] = 'before';
        /// fn($coreFunction, $layer[3]), $layer[2])
        /// $object->runs[] = 'after';
        /// Then we have
        /// $object->runs[] = 'before';
        /// fn($coreFunction, $layer[3])
        /// $object->runs[] = 'after';
        /// $object->runs[] = 'after';
        /// Then we have
        /// $object->runs[] = 'before'
        /// $object->runs[] = 'before';
        /// $coreFunction($output);
        /// $object->runs[] = 'after';
        /// $object->runs[] = 'after';
        

        // We now have the complete onion and can start passing the object
        // down through the layers.
        return $completeOnion($object);
    }

    /**
     * Get the layers of this onion, can be used to merge with another onion
     * @return array
     */
    public function toArray()
    {
        return $this->layers;
    }

    /**
     * The inner function of the onion.
     * This function will be wrapped on layers
     * @param  Closure $core the core function
     * @return Closure
     */
    private function createCoreFunction(Closure $core)
    {
        return function($object) use($core) {
            return $core($object);
        };
    }

    /**
     * Get an onion layer function.
     * This function will get the object from a previous layer and pass it inwards
     * @param  LayerInterface $nextLayer
     * @param  LayerInterface $layer
     * @return Closure
     */
    private function createLayer($nextLayer, $layer)
    {
        return function($object) use($nextLayer, $layer){
            return $layer->peel($object, $nextLayer);
        };
    }

}
