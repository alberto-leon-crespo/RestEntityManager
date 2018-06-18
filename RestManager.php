<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12/02/18
 * Time: 20:57
 */

namespace ALC\RestEntityManager;

use ALC\RestEntityManager\Services\Log\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use Monolog\Formatter\JsonFormatter;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RestManager
{
    protected $config;

    /**
     * @var $lastRequestException RequestException
     */
    protected $lastRequestException;
    private $session;
    private $guzzleHttpClient;
    private $guzzleHttpConnections;
    private $guzzleHttpCookieJar;
    private $logger;

    /**
     * @var $requestLog \GuzzleHttp\Message\RequestInterface
     */
    private $requestLog;

    /**
     * @var $responseLog \GuzzleHttp\Message\ResponseInterface
     */
    private $responseLog;

    public function __construct( array $config, SessionInterface $session, Logger $logger ){

        $this->config = $config;
        $this->session = $session;
        $this->logger = $logger;

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

            $this->requestLog = $objRequest;

            $objResponse = $this->guzzleHttpClient->send( $objRequest );

            $this->responseLog = $objResponse;

            $this->writeLog();

            return $objResponse;

        }catch ( RequestException $requestException ){

            $this->lastRequestException = $requestException;

            $this->requestLog = $requestException->getRequest();

            $this->responseLog = $requestException->getResponse();

            $this->writeLog();

            return $requestException->getResponse();

        }
    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function get( $path, $arrParameters = array(), array $arrHeaders = array() )
    {
        return $this->doRequest( $path, 'GET', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function post( $path, $arrParameters = array(), $arrHeaders = array() )
    {
        return $this->doRequest( $path, 'POST', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function put( $path, $arrParameters = array(), $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'PUT', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function path( $path, $arrParameters = array(), $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'PATH', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function head( $path, $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'HEAD', array(), $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrParameters
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function trace( $path, $arrParameters = array(), $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'TRACE', $arrParameters, $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function options( $path, $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'OPTIONS', array(), $arrHeaders );

    }

    /**
     * @param $path
     * @param array $arrHeaders
     * @return \GuzzleHttp\Message\Request|\GuzzleHttp\Message\RequestInterface|\GuzzleHttp\Message\ResponseInterface|null
     */
    public function delete( $path, $arrHeaders = array() )
    {

        return $this->doRequest( $path, 'DELETE', array(), $arrHeaders );

    }

    public function getConfigParams(){

        return $this->config;

    }

    public function getConfigParam( $strParamName ){

        if( array_key_exists( $strParamName, $this->config ) ){

            return $this->config[ $strParamName ];

        }

        return null;

    }

    private function writeLog(){

        $date = new \DateTime();

        $arrRequest = array(
            'request' => array(
                'uri' => $this->requestLog->getHost() . $this->requestLog->getPath(),
                'headers' => $this->requestLog->getHeaders(),
                'method' => $this->requestLog->getMethod(),
                'body' => ( $this->isJson( (string)$this->responseLog->getBody() ) ) ? json_decode( (string)$this->requestLog->getBody() ) : (string)$this->requestLog->getBody(),
                'query' => $this->requestLog->getQuery()
            ),
            'response' => array(
                'status' => $this->responseLog->getStatusCode(),
                'headers' => $this->responseLog->getHeaders(),
                'body' => ( $this->isJson( (string)$this->responseLog->getBody() ) ) ? json_decode( (string)$this->responseLog->getBody() ) : (string)$this->responseLog->getBody()
            )
        );

        $jsonFormatter = new JsonFormatter();

        if( !empty( $this->logger->getLogger( $this->config['name'] ) ) ){

            $this->logger->getLogger( $this->config['name'] )->info( $jsonFormatter->format( $arrRequest ) );

        }

    }

    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}