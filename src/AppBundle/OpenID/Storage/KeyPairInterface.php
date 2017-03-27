<?php

namespace AppBundle\OpenID\Storage;

interface KeyPairInterface
{
    public function getPublicKey();
    public function getPrivateKey();
    public function getEncryptionAlgorithm();
}
