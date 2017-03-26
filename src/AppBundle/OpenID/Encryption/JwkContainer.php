<?php

namespace AppBundle\OpenID\Encryption;

class JwkContainer
{
    /** @var  array */
    protected $keys;

    public function __construct($keys = array())
    {
        $this->keys = $keys;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param array $keys
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
    }
}
