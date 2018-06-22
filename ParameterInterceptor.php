<?php
/**
 * Created by PhpStorm.
 * User: aleon
 * Date: 22/06/2018
 * Time: 15:08
 */

namespace ALC\RestEntityManager;


use ALC\RestEntityManager\Services\MetadataClassReader\MetadataClassReader;
use Symfony\Component\HttpFoundation\ParameterBag;

class ParameterInterceptor
{
    private $metadataClassReader;

    public function __construct(MetadataClassReader $metadataClassReader)
    {
        $this->metadataClassReader = $metadataClassReader;
    }

    protected function getMetadataClassReader(){
        return $this->metadataClassReader;
    }
}