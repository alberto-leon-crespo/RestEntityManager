<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17/02/18
 * Time: 6:16
 */

namespace ALC\RestEntityManager\Subscribers;


use ALC\RestEntityManager\Services\MetadataClassReader\MetadataClassReader;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use JMS\Serializer\Construction\UnserializeObjectConstructor;

class JMSEventSubscriber implements EventSubscriberInterface
{
    private $fieldsMap;
    private $fieldsValues;
    private $fieldsType;
    private $annotationReader;
    private $attributesBag;
    private $doctrineObjectConstructor;
    private $anidateObject = false;
    private $classReader;

    public function __construct( RequestStack $requestStack, UnserializeObjectConstructor $doctrineObjectConstructor, MetadataClassReader $classReader )
    {
        $this->attributesBag = $requestStack->getMasterRequest()->attributes;
        $this->fieldsMap = $this->attributesBag->get('alc_entity_rest_client.handler.fieldsMap');
        $this->fieldsValues = $this->attributesBag->get('alc_entity_rest_client.handler.fieldsValues');
        $this->fieldsType = $this->attributesBag->get('alc_entity_rest_client.handler.fieldsType');
        $this->classReader = $classReader;
        $this->doctrineObjectConstructor = $doctrineObjectConstructor;
    }

    static public function getSubscribedEvents()
    {
        return array(
            array(
                'event' => 'serializer.pre_deserialize',
                'method' => 'onserializerPreDeserialize'
            ),
            array(
                'event' => 'serializer.pre_serialize',
                'method' => 'onserializerPreSerialize'
            )
        );
    }

    public function onserializerPreDeserialize( PreDeserializeEvent $event ){

        $type = $event->getType();
        $context = $event->getContext();
        $data = $event->getData();
        $visitor = $event->getVisitor();

        $classMetadata = $event->getContext()->getMetadataFactory()->getMetadataForClass( $type['name'] );

        if( empty( $this->fieldsMap ) ){

            $this->readClassAnnotations( $type['name'] );
            $this->anidateObject = true;

        }

        foreach( $this->fieldsMap as $originalFieldName => $targetFieldName ){

            if( array_key_exists( $originalFieldName, $this->fieldsMap ) ){

                $classMetadata->propertyMetadata[$originalFieldName]->serializedName = $targetFieldName;
                $classMetadata->propertyMetadata[$originalFieldName]->xmlEntryName = $targetFieldName;
                $classMetadata->propertyMetadata[$originalFieldName]->xmlCollectionSkipWhenEmpty = false;
                $classMetadata->propertyMetadata[$originalFieldName]->xmlElementCData = false;

                $classMetadata->propertyMetadata[$originalFieldName]->type = array(
                    'name' => $this->fieldsType[$originalFieldName],
                    'params' => []
                );

                unset( $this->fieldsMap[ $originalFieldName ] );

            }

        }

        if( $this->anidateObject ){

            return $this->doctrineObjectConstructor->construct( $visitor, $classMetadata, $data, $type, $context );

        }

        $context->pushClassMetadata( $classMetadata );

        return $event;

    }

    public function onserializerPreSerialize( PreSerializeEvent $event ){

        $type = $event->getType();
        $context = $event->getContext();
        $data = $event->getObject();
        $visitor = $event->getVisitor();

        $classMetadata = $event->getContext()->getMetadataFactory()->getMetadataForClass( $type['name'] );

        $this->readClassAnnotations( $type['name'] );

        foreach( $this->fieldsMap as $originalFieldName => $targetFieldName ){

            if( array_key_exists( $originalFieldName, $this->fieldsMap ) ){

                $classMetadata->propertyMetadata[$originalFieldName]->serializedName = $targetFieldName;
                $classMetadata->propertyMetadata[$originalFieldName]->xmlEntryName = $targetFieldName;
                $classMetadata->propertyMetadata[$originalFieldName]->xmlCollectionSkipWhenEmpty = false;
                $classMetadata->propertyMetadata[$originalFieldName]->xmlElementCData = false;

                $classMetadata->propertyMetadata[$originalFieldName]->type = array(
                    'name' => $this->fieldsType[$originalFieldName],
                    'params' => []
                );

                unset( $this->fieldsMap[ $originalFieldName ] );

            }

        }

        return $classMetadata->serialize();

    }

    private function readClassAnnotations( $classNamespace ){

        $this->classReader->readClassAnnotations( $classNamespace );

        $this->fieldsType = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsType');
        $this->fieldsMap = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsMap');
        $this->fieldsValues = $this->attributesBag->get( 'alc_entity_rest_client.handler.fieldsValues');

    }
}