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

class RestRepository extends RestManager
{
    protected $serializer;

    public function __construct( array $config, SessionInterface $session, Serializer $serializer, Logger $logger ){

        parent::__construct( $config, $session, $logger );

        $this->serializer = $serializer;

    }
}