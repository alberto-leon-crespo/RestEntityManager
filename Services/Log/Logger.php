<?php
/**
 * Created by PhpStorm.
 * User: aleon
 * Date: 27/02/2018
 * Time: 14:34
 */

namespace ALC\RestEntityManager\Services\Log;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;

class Logger
{
    private $logger;

    public function __construct( array $restEntityManagerConfig, $strKernelLogDir )
    {
        $this->logger = new MonologLogger('rest_entity_manager_logger');
        
        foreach( $restEntityManagerConfig['managers'] as $managerName => $manager ){
            
            $logHandler = new StreamHandler( $strKernelLogDir . "/rest_entity_" . $managerName . ".log", MonologLogger::INFO, true );
            $this->logger->pushHandler( $logHandler );
            
        }

        return $this;
    }

    /**
     * @return MonologLogger
     */
    public function getLogger(){

        return $this->logger;

    }
}