<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20/06/18
 * Time: 0:27
 */

namespace ALC\RestEntityManager\Services\ParametersProcessor;

use Symfony\Component\HttpFoundation\RequestStack;

class ParametersProcessor{

    private $request;
    private $attributesBag;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getMasterRequest();
        $this->attributesBag = $requestStack->getMasterRequest()->attributes;
    }

    public function processParameters( array $arrConfig, $destinationParameters )
    {
        $arrParameters = array();

        $arrFieldsMap = array_flip( $this->attributesBag->get('alc_entity_rest_client.handler.fieldsMap') );

        foreach( $arrConfig['ignored_parameters'] as $fieldToIgnore ){

            if( array_key_exists( $fieldToIgnore, $arrParameters ) ){
                unset( $arrParameters[$fieldToIgnore] );
            }

            if( array_key_exists( $fieldToIgnore, array_flip($arrFieldsMap) ) ){
                unset( $destinationParameters[array_flip($arrFieldsMap)[$fieldToIgnore]] );
            }

        }

        foreach( $arrConfig['parameters_map']['maps'] as $mapInfo ){
            $originalValue = $this->request->query->get($mapInfo['origin'], null);
            if($mapInfo['origin'] !== null && $mapInfo['destination'] !== null){
                $arrParameters[$mapInfo['destination']] = $originalValue;
            }else if($mapInfo['origin'] !== null && $mapInfo['interceptor'] !== null){
                $classInterceptorInfo = explode("::", $mapInfo['interceptor']);
                $classNameSpace = "\\" . $classInterceptorInfo[0];
                $classMethodToCall = $classInterceptorInfo[1];
                $classCallInstance = new $classNameSpace();
                $arrParameters += $classCallInstance->{$classMethodToCall}($originalValue,array_flip($arrFieldsMap));
            }
        }

        foreach( $arrParameters as $parameterName => $parameterValue ){
            if($parameterValue === null){
                unset( $arrParameters[$parameterName] );
            }
        }

        $arrParameters += $destinationParameters;

        return $arrParameters;
    }
}