<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12/02/18
 * Time: 20:57
 */

namespace ALC\RestEntityManagerBundle;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class RestManager
{
    protected $config;
    private $session;
    private $guzzleHttpClient;
    private $guzzleHttpConnections;
    private $guzzleHttpCookieJar;

    protected function __construct( array $config, SessionInterface $session ){

        $this->config = $config;
        $this->session = $session;

        $this->guzzleHttpConnections = $this->session->get('alc_entity_rest_client.active_connections');
        $this->guzzleHttpCookieJar = ( $this->guzzleHttpConnections !== null && array_key_exists( $this->config['session_name'], $this->guzzleHttpConnections ) ) ? $this->guzzleHttpConnections[ $this->config['session_name'] ] : new CookieJar();

        $this->guzzleHttpClient = new Client([
            'verify' => false
        ]);

        return $this;

    }

    /**
     * @param $strPath
     * @param $strMethod
     * @param array $arrParams
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function doRequest( $strPath, $strMethod, $arrParams = array(), array $arrHeaders = array() ){

        $arrGuzzleHttpOptions = array();

        if( !empty( $arrParams ) ){

            if( strtolower( $strMethod ) == "get" ){

                $arrGuzzleHttpOptions['query'] = $arrParams;

            }else{

                $arrGuzzleHttpOptions['body'] = $arrParams;

            }

        }

        if( !empty( $arrHeaders ) ){

            $arrGuzzleHttpOptions['headers'] = $arrHeaders;

        }

        try{

            $arrGuzzleHttpOptions['cookies'] = $this->guzzleHttpCookieJar;

            $objRequest = $this->guzzleHttpClient->createRequest( $strMethod, $this->config['host'] . $strPath, $arrGuzzleHttpOptions );

            $objResponse = $this->guzzleHttpClient->send( $objRequest );

            return $objResponse;

        }catch ( RequestException $requestException ){

            return $requestException->getResponse();

        }
    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function get( $path, $arrParameters = array(), array $arrHeaders = array() )
    {
        return $this->doRequest( $path, 'GET', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function post( $path, $arrParameters = array(), $arrHeaders = array() )
    {
        return $this->doRequest( $path, 'POST', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function put( $path, $arrParameters = array(), $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'PUT', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function path( $path, $arrParameters = array(), $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'PATH', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function head( $path, $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'HEAD', array(), $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function trace( $path, $arrParameters = array(), $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'TRACE', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function options( $path, $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'OPTIONS', array(), $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    protected function detete( $path, $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'DELETE', array(), $arrHeaders );

    }
}