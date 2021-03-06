<?php
/**
 * Created by PhpStorm.
 * User: aleon
 * Date: 12/02/18
 * Time: 19:54
 */

namespace ALC\RestEntityManager\Services\RestEntityHandler;

use ALC\RestEntityManager\RestManager;
use ALC\RestEntityManager\Services\MetadataClassReader\MetadataClassReader;
use ALC\RestEntityManager\Services\Log\Logger;
use ALC\RestEntityManager\Services\ParametersProcessor\ParametersProcessor;
use ALC\RestEntityManager\Services\RestEntityHandler\Exception\HttpError;
use ALC\RestEntityManager\Services\RestEntityHandler\Exception\InvalidParamsException;
use ALC\RestEntityManager\Utils\ArrayUtilsClass;
use ALC\RestEntityManager\Utils\HttpConstants;
use Arrayy\Arrayy;
use GuzzleHttp\Message\Response;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use ALC\RestEntityManager\Services\RestEntityHandler\Exception\RunTimeException;

class RestEntityHandler
{
    private $bundleConfig;
    private $session;
    private $bundles;
    private $serializer;
    private $attributesBag;
    private $classReader;
    private $path;
    private $fieldsMap;
    private $fieldsType;
    private $fieldsValues;
    private $entityIdValue;
    private $entityIdFieldName;
    private $entityRepository;
    private $logger;
    private $parametersProcesor;

    /**
     * @var $restManager RestManager
     */
    private $restManager;

    private $headers = array(
        'content-type' => 'application/json'
    );

    private $deserilizationFormats = array(
        'application/json' => 'json',
        'application/xml' => 'xml',
        'application/html' => 'json',
        'text/html' => 'xml',
    );

    private $serializationFormats = array(
        'application/json' => 'json',
        'application/xml' => 'xml',
        'application/html' => 'json',
        'text/html' => 'xml',
    );

    /**
     * RestEntityHandler constructor.
     * @param array $config
     * @param SessionInterface $session
     * @param array $bundles
     * @param Serializer $serializer
     * @param RequestStack $requestStack
     */
    public function __construct(
        array $config,
        SessionInterface $session,
        array $bundles,
        Serializer $serializer,
        RequestStack $requestStack,
        Logger $logger,
        MetadataClassReader $classReader,
        ParametersProcessor $parametersProcessor
    ){

        $this->bundleConfig = $config;
        $this->session = $session;
        $this->bundles = $bundles;
        $this->serializer = $serializer;
        $this->attributesBag = $requestStack->getMasterRequest()->attributes;
        $this->classReader = $classReader;
        $this->logger = $logger;
        $this->fieldsToShow = [];
        $this->parametersProcesor = $parametersProcessor;

        return $this;

    }

    /**
     * @param null $strManagerName
     * @return \ALC\RestEntityManager\Services\RestEntityHandler\RestEntityHandler $this
     */
    public function getManager( $strManagerName = null ){

        if( empty( $strManagerName ) ){

            $strManagerName = $this->bundleConfig['default_manager'];

            $config = $this->bundleConfig['managers'][$strManagerName];
        }

        if( !array_key_exists( $strManagerName, $this->bundleConfig['managers'] ) ){

            throw new InvalidParamsException( 400, "Restmanager that you can try to load <$strManagerName> is not defined under alc_entity_rest_client.managers" );

        }

        $config = $this->bundleConfig['managers'][$strManagerName];

        $this->restManager = new RestManager( $config, $this->session, $this->logger );

        return $this;
    }

    /**
     * @param $strPersistenceObjectName
     * @return $this
     */
    public function getRepository( $strPersistenceObjectName ){

        $strRepositoryPath = explode(":", $strPersistenceObjectName );

        $bundleName = $strRepositoryPath[0];
        $entityPath = $strRepositoryPath[1];

        if( !array_key_exists( $bundleName, $this->bundles ) ){

            throw new InvalidParamsException( 400, "Bundle <$bundleName> doesn't exist loaded  in symfony configuration." );

        }

        $classNamespaceParts = explode("\\", $this->bundles[$bundleName] );

        array_pop( $classNamespaceParts );

        $classNamespace = implode("\\", $classNamespaceParts );

        $classNamespace = $classNamespace . "\\Entity\\" . $entityPath;

        if( !class_exists( $classNamespace ) ){

            throw new InvalidParamsException( 400, "Class $classNamespace doesn't exist." );

        }

        $this->readClassAnnotations( $classNamespace );

        $respositoryInstance = new $this->entityRepository( $this->restManager, $this->serializer );

        $reflectionClass = new \ReflectionClass( $respositoryInstance );

        $methods = $reflectionClass->getMethods();

        foreach( $methods as $method ){

            if( $method->isPublic() ){

                $this->{$method->getName()} = function() use ( $respositoryInstance, $method ){

                    $closure = $method->getClosure( $respositoryInstance );

                    $parameters = $method->getParameters();

                    $arrArgs = array();

                    foreach( $parameters as $reflectionParameter ){

                        $arrArgs[] = $reflectionParameter->getName();

                    }

                    if( is_callable( $closure ) ){

                        return call_user_func_array( $closure, $arrArgs );

                    }


                };

            }

        }

        return $this;

    }

    public function __call( $method, $arguments = array() )
    {
        if( is_callable( array( $this, $method ) ) ){

            return call_user_func_array( $this->$method, $arguments );

        }
    }

    /**
     * @param $id
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function find( $id, $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $response = $this->restManager->get( $this->path . "/" . $id, array(), $this->headers );

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray );
    }

    /**
     * @param array $arrFilters
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function findBy( array $arrFilters, $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $className = $this->parseClassNamespace( $objClass );

        $filteringConfig = $this->restManager->getConfigParams()['avanced']['filtering'];

        $arrParamsToAdd = $this->parametersProcesor->processParameters( $filteringConfig, $className, $arrFilters );

        $response = $this->restManager->get( $this->path, $arrParamsToAdd, $this->headers );

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray );
    }

    /**
     * @param array $arrFilters
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return mixed
     */
    public function findOneBy( array $arrFilters, $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $className = $this->parseClassNamespace( $objClass );

        $filteringConfig = $this->restManager->getConfigParams()['avanced']['filtering'];

        $arrParamsToAdd = $this->parametersProcesor->processParameters( $filteringConfig, $className, $arrFilters );

        $response = $this->restManager->get( $this->path, $arrParamsToAdd, $this->headers );

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray )[0];
    }

    /**
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function findAll( $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $className = $this->parseClassNamespace( $objClass );

        $filteringConfig = $this->restManager->getConfigParams()['avanced']['filtering'];

        $arrParamsToAdd = $this->parametersProcesor->processParameters( $filteringConfig, $className, array() );

        $response = $this->restManager->get( $this->path, $arrParamsToAdd, $this->headers );

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray );
    }

    /**
     * @param $object
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function persist( $object, $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $this->readClassAnnotations( $object );

        $arrHeaders = array();
        $serializationFormat = 'json';

        foreach( $this->headers as $header => $value ){

            if( strpos( 'content-type', $header ) !== false  ||  strpos( 'Content-type', $header ) !== false || strpos( 'CONTENT-TYPE', $header ) !== false ){

                $headers[$header] = $value;

                $serializationFormat = $this->serializationFormats[$value];

                if( !array_key_exists( $value, $this->serializationFormats ) ){

                    throw new RunTimeException(400, "Content type serialization is not suported. Suported types are " . implode( ",", $this->serializationFormats ) );

                }

            }

        }

        $payload = $this->serializer->serialize( $object, $serializationFormat );

        if( $this->entityIdValue !== null ){

            $response = $this->restManager->put( $this->path . '/' . $this->entityIdValue, $payload, $arrHeaders );

        }else{

            $response = $this->restManager->post( $this->path, $payload, $arrHeaders );

        }

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray );
    }

    /**
     * @param $object
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function remove( $object, $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $this->readClassAnnotations( $object );

        $arrHeaders = array();

        foreach( $this->headers as $header => $value ){

            if( strpos( 'content-type', $header ) !== false  ||  strpos( 'Content-type', $header ) !== false || strpos( 'CONTENT-TYPE', $header ) !== false ){

                $headers[$header] = $value;

                if( !array_key_exists( $value, $this->serializationFormats ) ){

                    throw new RunTimeException(400, "Content type serialization is not suported. Suported types are " . implode( ",", $this->serializationFormats ) );

                }

            }

        }

        $response = $this->delete( $this->path . '/' . $this->entityIdValue, $arrHeaders );

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray );
    }

    /**
     * @param $object
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function merge( $object, $format = 'json', $objClass = null, $objectsToArray = false ){

        $this->readClassAnnotations( $object );

        $arrHeaders = array();
        $serializationFormat = 'json';

        foreach( $this->headers as $header => $value ){

            if( strpos( 'content-type', $header ) !== false  ||  strpos( 'Content-type', $header ) !== false || strpos( 'CONTENT-TYPE', $header ) !== false ){

                $headers[$header] = $value;

                $serializationFormat = $this->serializationFormats[$value];

                if( !array_key_exists( $value, $this->serializationFormats ) ){

                    throw new RunTimeException(400, "Content type serialization is not suported. Suported types are " . implode( ",", $this->serializationFormats ) );

                }

            }

        }

        $response = $this->find( $this->entityIdValue, 'json' );

        $payload = $this->serializer->serialize( $object, $serializationFormat );

        $idFieldName = $this->entityIdFieldName;
        $keyExist = false;

        array_walk_recursive( $response, function( $key ) use ( $idFieldName, &$keyExist ){

            if( $key == $idFieldName ){

                $keyExist = true;

            }

        });

        if( $keyExist ){

            $response = $this->restManager->put( $this->path, $payload, $arrHeaders );

        }else{

            $response = $this->restManager->post( $this->path, $payload, $arrHeaders );

        }

        return $this->deserializeResponse( $response, $format, $objClass, $objectsToArray );
    }

    /**
     * @param $object
     * @param string $format
     * @param null $objClass
     * @param bool $objectsToArray
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    public function refresh( &$object, $format = 'json', $objClass = null, $objectsToArray = false )
    {
        $this->readClassAnnotations( $object );

        $refreshingData = $this->find( $this->entityIdValue, $format, $objClass, $objectsToArray );

        $idFieldName = $this->entityIdFieldName;
        $keyExist = false;

        array_walk_recursive( $response, function( $key ) use ( $idFieldName, &$keyExist ){

            if( $key == $idFieldName ){

                $keyExist = true;

            }

        });

        if( $keyExist ){

            $object = $refreshingData;

            return $object;

        }

        return $object;
    }

    /**
     * @param $classNamespace
     */
    private function readClassAnnotations( $classNamespace ){

        $this->classReader->readClassAnnotations( $classNamespace );

        $this->fieldsType = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsType');
        $this->fieldsMap = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsMap');
        $this->fieldsValues = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsValues');
        $this->path = $this->attributesBag->get( 'alc_entity_rest_client.handler.path' );
        $this->headers = $this->attributesBag->get( 'alc_entity_rest_client.handler.headers' );
        $this->entityRepository = $this->attributesBag->get( 'alc_entity_rest_client.handler.entityRespository' );
        $this->entityIdValue = $this->attributesBag->get( 'alc_entity_rest_client.handler.entityIdValue' );
        $this->entityIdFieldName = $this->attributesBag->get( 'alc_entity_rest_client.handler.entityIdFieldName' );

    }

    /**
     * @param Response $response
     * @param string $format
     * @param null $objClass
     * @return array|\JMS\Serializer\scalar|mixed|object
     */
    private function deserializeResponse( Response $response, $format = 'json', $objClass = null, $objectsToArray = false ){

        foreach( $this->deserilizationFormats as $header => $availableFormat ){

            if( strpos( $response->getHeader('content-type'), $header ) !== false ){

                $detectedFormat = true;
                $deserializeFormat = $this->deserilizationFormats[$header];

            }

        }

        if( $format == 'json' ){

            return $response->json();

        }else if( $format == 'xml' ){

            return $response->xml();

        }else if( $format = 'object' && $objClass !== null ){

            if( $detectedFormat === false ){

                throw new RunTimeException(400, "Unserialized format <" . $response->getHeader('content-type') . "> is not supported.", null, $response->getHeaders(), 0 );

            }

            if( !in_array( $response->getStatusCode(), HttpConstants::$sucessResponses ) ){

                throw new HttpError($response->getStatusCode(), $this->restManager->getLastRequestException()->getMessage(), $this->restManager->getLastRequestException(), $this->restManager->getLastRequestException()->getResponse()->getHeaders(), $this->restManager->getLastRequestException()->getCode() );

            }

            $className = $this->parseClassNamespace( $objClass );

            $this->readClassAnnotations( $className );
            
            $response = $this->serializer->deserialize( (string)$response->getBody(), $objClass, $deserializeFormat );

            if( $objectsToArray ){

                return ArrayUtilsClass::recursiveObjectToArray( $response );

            }else{

                return $response;

            }

        }

    }

    private function parseClassNamespace($strClassNamespace){

        $strClassNamespace = str_replace( "array", "", $strClassNamespace );
        $strClassNamespace = str_replace( "<", "", $strClassNamespace );
        $strClassNamespace = str_replace( ">", "", $strClassNamespace );

        return $strClassNamespace;
    }
}