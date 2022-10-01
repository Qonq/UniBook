<?php

namespace App\Controller;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;




class SerializerAwareAbstractController extends AbstractController
{

    private $serializer;


    protected function getSerializer()
    {
        if(!isset($this->serializer)){
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);
            // what the serialiser should do when it reaches its MaxDepth when serializing table relations
            $defaultContext = [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                    return $object->getId();
                } ];

            $this->serializer = new Serializer([new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, null, null, null, null, $defaultContext)], [new JsonEncoder()]);
        }
        return $this->serializer;
    }

}