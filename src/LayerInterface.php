<?php

namespace Optimus\Onion;

use \Closure;

interface LayerInterface {

    public function peel($object, Closure $next);

}
