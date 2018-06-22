<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18/06/18
 * Time: 23:24
 */

namespace ALC\RestEntityManager\Services\MetadataClassReader;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\RequestStack;

class MetadataClassReader
{
    private $annotationReader;
    private $attributesBag;
    private $arrayMatchedParams = [];
    private $entityFinalFilterPath = "";

    public function __construct( RequestStack $requestStack )
    {
        $this->annotationReader = new AnnotationReader();
        $this->attributesBag = $requestStack->getMasterRequest()->attributes;
    }

    /**
     * @param $classNamespace
     */
    public function readClassAnnotations( $classNamespace ){

        $objClassInstanceReflection = new \ReflectionClass( $classNamespace );

        $fieldsType = [];
        $fieldsMap = [];
        $fieldsValues = [];
        $path = "";
        $headers = [];
        $entityRepository = [];
        $entityIdValue = [];
        $entityIdFieldName = [];

        if( !empty( $this->annotationReader->getClassAnnotations( $objClassInstanceReflection ) ) ){

            foreach( $this->annotationReader->getClassAnnotations( $objClassInstanceReflection ) as $annotation ){

                if( get_class( $annotation ) == "ALC\\RestEntityManager\\Annotations\\Resource" ){

                    $path = $annotation->getValue();

                }

                if( get_class( $annotation ) == "ALC\\RestEntityManager\\Annotations\\Headers" ){

                    $headers = $annotation->getValues();

                }

                if( get_class( $annotation ) == "ALC\\RestEntityManager\\Annotations\\Repository" ){

                    $entityRepository = $annotation->getRepositoryClass();

                }

            }

        }

        if( !empty( $objClassInstanceReflection->getProperties() ) ){

            foreach( $objClassInstanceReflection->getProperties() as $property ){

                $property->setAccessible( true );

                $arrPropertiesAnnotations = $this->annotationReader->getPropertyAnnotations( $property );

                foreach( $arrPropertiesAnnotations as $propertyAnnotation ){

                    if( get_class( $propertyAnnotation ) == "ALC\\RestEntityManager\\Annotations\\Field" ){

                        $fieldsMap[ $property->getName() ] = $propertyAnnotation->getTarget();
                        $fieldsType[ $property->getName() ] = $propertyAnnotation->getType();

                        if( is_object( $classNamespace ) ){

                            $fieldsValues[ $property->getName() ] = $property->getValue( $classNamespace );

                        }else{

                            $fieldsValues[ $property->getName() ] = null;

                        }
                    }

                    if( get_class( $propertyAnnotation ) == "ALC\\RestEntityManager\\Annotations\\Id" ){

                        if( is_object( $classNamespace ) ){

                            $entityIdValue = $property->getValue( $classNamespace );

                        }

                        $entityIdFieldName = $property->getName();

                    }

                }

            }

            $this->attributesBag->set( 'alc_entity_rest_client.handler.fieldsMap', $fieldsMap );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.fieldsType', $fieldsType );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.fieldsValues', $fieldsValues );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.path', $path );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.headers', $headers );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.entityRespository', $entityRepository );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.entityIdValue', $entityIdValue );
            $this->attributesBag->set( 'alc_entity_rest_client.handler.entityIdFieldName', $entityIdFieldName );
        }
    }

    public function flushMatchedParams(){
        $this->arrayMatchedParams = [];
        $this->entityFinalFilterPath = "";
    }

    public function matchEntityFieldsWithResourcesFieldsRecursive( $array ){

        $arrFieldsMap = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsMap');
        $fieldsType = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsType');

        foreach( $array as $propertyName => $value ){

            $path = explode( ".", $propertyName );

            $field = array_shift( $path );

            if( strpos( $propertyName, "." ) !== false ){

                if( !empty( $field ) ){

                    if( array_key_exists( $field, $arrFieldsMap ) ){

                        if( class_exists( $fieldsType[$field] ) ){

                            $this->entityFinalFilterPath .= "." . $arrFieldsMap[$field];

                            $this->readClassAnnotations( $fieldsType[$field] );

                            $array = array(
                                implode( ".", $path ) => $value
                            );

                            $this->matchEntityFieldsWithResourcesFieldsRecursive( $array );

                        }else{

                            if( array_key_exists( $field, $arrFieldsMap ) ){

                                $this->entityFinalFilterPath .= "." . $arrFieldsMap[ $field ];

                                $this->arrayMatchedParams[ $this->entityFinalFilterPath ] = $value;

                            }

                            $this->readClassAnnotations( $fieldsType[$field] );

                            $array = array(
                                implode( ".", $path ) => $value
                            );

                            $this->matchEntityFieldsWithResourcesFieldsRecursive( $array );

                        }

                    }

                }

            }else{

                if( array_key_exists( $field, $arrFieldsMap ) ){

                    $this->entityFinalFilterPath .= "." . $arrFieldsMap[ $field ];

                    $this->entityFinalFilterPath = substr( $this->entityFinalFilterPath, 1 );

                    $this->arrayMatchedParams[ $this->entityFinalFilterPath ] = $value;

                }

            }

        }

        return $this->arrayMatchedParams;
    }

    public function mathResourceFieldsWithEntityFieldsRecursive( $array ){

        $arrFieldsMapOriginal = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsMap');
        $arrFieldsMapReversed = array_flip( $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsMap') );
        $fieldsType = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsType');

        foreach( $array as $propertyName => $value ){

            $path = explode( ".", $propertyName );

            $field = array_shift( $path );

            if( strpos( $propertyName, "." ) !== false ){

                if( !empty( $field ) ){

                    if( array_key_exists( $field, $arrFieldsMapReversed ) ){

                        if( class_exists( $fieldsType[$arrFieldsMapReversed[$field]] ) ){

                            $this->entityFinalFilterPath .= "." . $arrFieldsMapReversed[$field];

                            $this->readClassAnnotations( $fieldsType[$arrFieldsMapReversed[$field]] );

                            $array = array(
                                implode( ".", $path ) => $value
                            );

                            $this->mathResourceFieldsWithEntityFieldsRecursive( $array );

                        }else{

                            if( array_key_exists( $field, $arrFieldsMapReversed ) ){

                                $this->entityFinalFilterPath .= "." . $arrFieldsMapReversed[ $field ];

                                $this->arrayMatchedParams[ $this->entityFinalFilterPath ] = $value;

                            }

                            $this->readClassAnnotations( $fieldsType[$arrFieldsMapReversed[$field]] );

                            $array = array(
                                implode( ".", $path ) => $value
                            );

                            $this->mathResourceFieldsWithEntityFieldsRecursive( $array );

                        }

                    }

                }

            }else{

                if( array_key_exists( $field, $arrFieldsMapReversed ) ){

                    $this->entityFinalFilterPath .= "." . $arrFieldsMapReversed[ $field ];

                    $this->entityFinalFilterPath = substr( $this->entityFinalFilterPath, 1 );

                    $this->arrayMatchedParams[ $this->entityFinalFilterPath ] = $value;

                }

            }

        }

        return $this->arrayMatchedParams;
    }
}