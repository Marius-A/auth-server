<?php

namespace AppBundle\Service;

use AppBundle\Utils\StringUtil;

class CryptService
{
    const SERVICE_NAME = 'crypt.service';

    /** @var  string */
    protected $secret;

    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    public function encrypt($plain)
    {
        $iv = StringUtil::randomString(openssl_cipher_iv_length("aes-256-ctr"));
        return openssl_encrypt($plain, "aes-256-ctr", $this->secret, false, $iv) . ":" . $iv;
    }

    public function decrypt($encrypted)
    {
        $decrypted = explode(":",$encrypted);
        if (!isset($decrypted[0]) || !isset($decrypted[1])) {
            throw new \Exception("Encrypted message is invalid");
        }
        return openssl_decrypt($decrypted[0], "aes-256-ctr", $this->secret, false, $decrypted[1]);
    }
}