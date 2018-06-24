<?php
/**
 * Created by PhpStorm.
 * User: aleon
 * Date: 24/06/2018
 * Time: 21:44
 */

namespace ALC\RestEntityManager\Interceptors;


class RequestInterceptor
{
    public function __call( $method, $arguments = array() )
    {
        if( is_callable( array( $this, $method ) ) ){
            return call_user_func_array( $this->$method, $arguments );
        }
    }
}