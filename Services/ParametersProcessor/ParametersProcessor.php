<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20/06/18
 * Time: 0:27
 */

namespace ALC\RestEntityManager\Services\ParametersProcessor;

use ALC\RestEntityManager\Services\MetadataClassReader\MetadataClassReader;
use Symfony\Component\HttpFoundation\RequestStack;

class ParametersProcessor{

    private $request;
    private $attributesBag;
    private $metadataClassReader;

    public function __construct(RequestStack $requestStack, MetadataClassReader $metadataClassReader)
    {
        $this->request = $requestStack->getMasterRequest();
        $this->attributesBag = $requestStack->getMasterRequest()->attributes;
        $this->metadataClassReader = $metadataClassReader;
    }

    public function processParameters( array $arrConfig, $classNameSpace, $arrFilters )
    {
        $this->metadataClassReader->readClassAnnotations( $classNameSpace );
        $arrParameters = $this->metadataClassReader->matchEntityFieldsWithResourcesFieldsRecursive( $arrFilters );

        $this->metadataClassReader->flushMatchedParams();
        $this->metadataClassReader->readClassAnnotations( $classNameSpace );

        $arrFieldsMap = array_flip( $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsMap') );

        foreach( $arrConfig['ignored_parameters'] as $fieldToIgnore ){

            if( array_key_exists( $fieldToIgnore, $arrParameters ) ){
                unset( $arrParameters[$fieldToIgnore] );
            }

            $arrParameters = $this->metadataClassReader->mathResourceFieldsWithEntityFieldsRecursive( $arrParameters );
            $this->metadataClassReader->flushMatchedParams();

            foreach( $arrParameters as $filterName => $filterValue ){
                if( array_key_exists( $fieldToIgnore, $arrParameters ) ){
                    unset( $arrParameters[$fieldToIgnore] );
                }
            }

            $this->metadataClassReader->readClassAnnotations( $classNameSpace );
            $arrParameters += $this->metadataClassReader->matchEntityFieldsWithResourcesFieldsRecursive( $arrParameters );
            $this->metadataClassReader->flushMatchedParams();
            $this->metadataClassReader->readClassAnnotations( $classNameSpace );

        }

        foreach( $arrConfig['parameters_map']['maps'] as $mapInfo ){
            $originalValue = $this->request->query->get($mapInfo['origin'], null);
            if($mapInfo['origin'] !== null && $mapInfo['destination'] !== null){
                $arrParameters[$mapInfo['destination']] = $originalValue;
            }else if($mapInfo['origin'] !== null && $mapInfo['interceptor'] !== null){
                $classInterceptorInfo = explode("::", $mapInfo['interceptor']);
                $classNameSpace = "\\" . $classInterceptorInfo[0];
                $classMethodToCall = $classInterceptorInfo[1];
                $classCallInstance = new $classNameSpace($this->metadataClassReader);
                $arrParameters += $classCallInstance->{$classMethodToCall}($originalValue);
            }
        }

        foreach( $arrParameters as $parameterName => $parameterValue ){
            if($parameterValue === null){
                unset( $arrParameters[$parameterName] );
            }
        }

        return $arrParameters;
    }
}