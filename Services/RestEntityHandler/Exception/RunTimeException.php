<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13/02/18
 * Time: 1:13
 */

namespace ALC\RestEntityManager\Services\RestEntityHandler\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RunTimeException extends HttpException
{
    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}