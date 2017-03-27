<?php

namespace AppBundle\OpenID\Storage;

class KeyPairParameterAdapter implements KeyPairInterface
{
    const ADAPTER_NAME = 'openid.storage.keypair_parameter.adapter';
    protected $encryptionAlgorithm;

    protected $certificate;

    protected $privateKey;

    /**
     * @param mixed $encryptionAlgorithm
     */
    public function setEncryptionAlgorithm($encryptionAlgorithm)
    {
        $this->encryptionAlgorithm = $encryptionAlgorithm;
    }

    /**
     * @param mixed $certificate
     */
    public function setCertificate($certificate)
    {
        $this->certificate = $certificate;
    }

    /**
     * @param mixed $privateKey
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function getPublicKey()
    {
        $publicKey = openssl_pkey_get_details(openssl_get_publickey($this->certificate));
        if (isset($publicKey['key'])) {
            return $publicKey['key'];
        }
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function getEncryptionAlgorithm()
    {
        return $this->encryptionAlgorithm;
    }
}
