<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 6/06/18
 * Time: 23:36
 */

namespace ALC\RestEntityManager\Listeners;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestParameterNameOverrideListener
{
    /**
     * @var $request \Symfony\Component\HttpFoundation\Request
     */
    private $request;
    private $queryParams;

    public function onKernelController(FilterControllerEvent $event)
    {
        $this->request = $event->getRequest();

        $this->clearQueryParams();
        
        $arrQueryParams = $this->getQueryParams();

        if( !empty( $arrQueryParams ) ){

            foreach( $arrQueryParams as $queryKey => $queryValue ) {

                $this->request->query->set($queryKey, $queryValue);

            }

        }

    }

    private function clearQueryParams(){

        $keys = $this->request->query->keys();

        foreach ( $keys as $key ){

            $this->request->query->remove( $key );

        }

    }

    private function getQueryParams(){

        $fullUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        if( !empty( $_SERVER['REQUEST_URI'] ) ) {

            if (strpos($fullUrl, ".") !== false) {

                $parsedUrl = parse_url($fullUrl);

                if (array_key_exists('query', $parsedUrl)) {

                    $queryParams = $parsedUrl['query'];

                    if (strpos($queryParams, "&") !== false) {

                        $queryParamsArray = explode("&", $queryParams);

                        foreach( $queryParamsArray as $queryParam ){

                            $queryParamData = explode( "=", $queryParam );

                            $this->queryParams[ $queryParamData[0] ] = rawurldecode( $queryParamData[1] );

                        }

                    } else {

                        $queryParamData = explode( "=", $queryParams );

                        $this->queryParams[ $queryParamData[0] ] = rawurldecode( $queryParamData[1] );

                    }

                }

            }

        }

        return $this->queryParams;

    }
}