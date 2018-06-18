<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 22/02/18
 * Time: 2:49
 */

namespace ALC\RestEntityManager\Services\Serializer;

use ALC\RestEntityManager\Services\MetadataClassReader\MetadataClassReader;
use ALC\RestEntityManagerBundle\Utils\ArrayUtilsClass;
use FOS\RestBundle\Context\Context;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Common\Annotations\AnnotationReader;

class Serializer implements \FOS\RestBundle\Serializer\Serializer
{
    private $serializationContextFactory;

    public function __construct( RequestStack $requestStack, UnserializeObjectConstructor $objectConstructor, MetadataClassReader $classReader )
    {
        $builder = SerializerBuilder::create();

        $builder->setPropertyNamingStrategy( new IdenticalPropertyNamingStrategy() );
        $builder->configureListeners( function( EventDispatcher $eventDispatcher ) use ( $requestStack, $objectConstructor, $classReader ){

            $attributesBag = $requestStack->getMasterRequest()->attributes;

            $eventDispatcher->addListener( 'serializer.pre_deserialize', function( PreDeserializeEvent $event ) use ( $attributesBag, $objectConstructor, $classReader ){

                $type = $event->getType();
                $context = $event->getContext();
                $data = $event->getData();
                $visitor = $event->getVisitor();
                $anidateObject = false;

                $classMetadata = $event->getContext()->getMetadataFactory()->getMetadataForClass( $type['name'] );

                if( empty( $fieldsMap ) ){

                    $classReader->readClassAnnotations( $type['name'] );

                    $fieldsMap = $attributesBag->get('alc_entity_rest_client.handler.fieldsMap');
                    $fieldsValues = $attributesBag->get('alc_entity_rest_client.handler.fieldsValues');
                    $fieldsType = $attributesBag->get('alc_entity_rest_client.handler.fieldsType');

                    $anidateObject = true;

                }

                foreach( $fieldsMap as $originalFieldName => $targetFieldName ){

                    if( array_key_exists( $originalFieldName, $fieldsMap ) ){

                        $classMetadata->propertyMetadata[$originalFieldName]->serializedName = $originalFieldName;
                        $classMetadata->propertyMetadata[$originalFieldName]->xmlEntryName = $originalFieldName;
                        $classMetadata->propertyMetadata[$originalFieldName]->xmlCollectionSkipWhenEmpty = false;
                        $classMetadata->propertyMetadata[$originalFieldName]->xmlElementCData = false;

                        $classMetadata->propertyMetadata[$originalFieldName]->type = array(
                            'name' => $fieldsType[$originalFieldName],
                            'params' => []
                        );

                        unset( $fieldsMap[ $originalFieldName ] );

                    }

                }

                if( $anidateObject ){

                    return $objectConstructor->construct( $visitor, $classMetadata, $data, $type, $context );

                }

                $context->pushClassMetadata( $classMetadata );

                return $event;

            } );
        } );

        $this->serializer = $builder->build();

        return $this;
    }

    public function serialize( $data, $format, Context $context = null ){

        return $this->serializer->serialize( $data, $format, $this->serializationContextFactory );

    }

    public function deserialize( $data, $format, $objectType, Context $context = null, $objectsToArray = false ){

        $response = $this->serializer->deserialize( $data, $objectType, $format );

        if( $objectsToArray ){

            return ArrayUtilsClass::recursiveObjectToArray( $response );

        }else{

            return $response;

        }

    }
}