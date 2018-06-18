<?php
/**
 * Created by PhpStorm.
 * User: aleon
 * Date: 26/02/2018
 * Time: 18:46
 */

namespace ALC\RestEntityManager;

use ALC\RestEntityManager\Services\Log\Logger;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RestRepository
{
    protected $serializer;
    protected $restManager;

    public function __construct( RestManager $restManager, Serializer $serializer ){

        $this->serializer = $serializer;
        $this->restManager = $restManager;

    }
}