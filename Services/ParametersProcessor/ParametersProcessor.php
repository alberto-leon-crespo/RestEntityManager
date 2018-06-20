<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20/06/18
 * Time: 0:27
 */

namespace ALC\RestEntityManager\Services\ParametersProcesor;

use Symfony\Component\HttpFoundation\RequestStack;

class ParametersProcesor{

    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    
}