<?php

namespace AppBundle\Service;

use JMS\Serializer\Context;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\scalar;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;

class SerializerService implements SerializerInterface
{
    /** @var  Serializer */
    protected $jmsSerializer;

    /**
     * @return Serializer
     */
    public function getJmsSerializer()
    {
        return $this->jmsSerializer;
    }

    /**
     * @param Serializer $jmsSerializer
     */
    public function setJmsSerializer($jmsSerializer)
    {
        $this->jmsSerializer = $jmsSerializer;
    }

    /**
     * Serializes the given data to the specified output format.
     *
     * @param array|scalar|object $data
     * @param string $format
     * @param SerializationContext|null $context
     * @return string
     */
    public function serialize($data, $format, SerializationContext $context = null)
    {
        return $this->jmsSerializer->serialize($data, $format, $context);
    }

    /**
     * Deserializes the given data to the specified type.
     *
     * @param string $data
     * @param string $type
     * @param string $format
     * @param DeserializationContext|null $context
     * @return mixed
     */
    public function deserialize($data, $type, $format, DeserializationContext $context = null)
    {
        return $this->jmsSerializer->deserialize($data, $type, $format, $context);
    }

} 