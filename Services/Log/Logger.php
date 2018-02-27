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
    private $loggers;

    public function __construct( array $restEntityManagerConfig, $strKernelLogDir )
    {

        foreach( $restEntityManagerConfig['managers'] as $managerName => $manager ){

            $this->loggers[ $managerName ] = new MonologLogger( 'rest_entity_manager_' . $managerName );

            $logHandler = new StreamHandler( $strKernelLogDir . "/rest_entity_" . $managerName . ".log", MonologLogger::INFO, true );

            $this->loggers[ $managerName ]->pushHandler( $logHandler );
            
        }

        return $this;
    }

    /**
     * @return MonologLogger
     */
    public function getLogger( $loggerName ){

        return array_key_exists( $loggerName, $this->loggers ) ? $this->loggers[ $loggerName ] : null;

    }
}