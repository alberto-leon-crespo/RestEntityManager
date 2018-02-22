<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12/02/18
 * Time: 20:53
 */

namespace ALC\RestEntityManagerBundle\Services\RestEntityHandler\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidParamsException extends HttpException
{
    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}