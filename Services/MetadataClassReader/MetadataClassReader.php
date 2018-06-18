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
}