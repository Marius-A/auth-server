<?php

namespace AppBundle\OpenID\Encryption;

use AppBundle\Utils\UrlUtil;

class Jwt implements EncryptionInterface
{
    public function encode($payload, $key, $algo = 'HS256')
    {
        $algo = $algo ?: 'HS256';
        
        $header = $this->generateJwtHeader($payload, $algo);

        $segments = array(
            UrlUtil::urlSafeB64Encode(json_encode($header)),
            UrlUtil::urlSafeB64Encode(json_encode($payload))
        );

        $signing_input = implode('.', $segments);

        $signature = $this->sign($signing_input, $key, $algo);
        $segments[] = UrlUtil::urlSafeB64Encode($signature);

        return implode('.', $segments);
    }

    public function decode($jwt, $key = null, $allowedAlgorithms = true)
    {
        if (!strpos($jwt, '.')) {
            return false;
        }

        $tks = explode('.', $jwt);

        if (count($tks) != 3) {
            return false;
        }

        list($headb64, $payloadb64, $cryptob64) = $tks;

        if (null === ($header = json_decode(UrlUtil::urlSafeB64Decode($headb64), true))) {
            return false;
        }

        if (null === $payload = json_decode(UrlUtil::urlSafeB64Decode($payloadb64), true)) {
            return false;
        }

        $sig = UrlUtil::urlSafeB64Decode($cryptob64);

        if ((bool)$allowedAlgorithms) {
            if (!isset($header['alg'])) {
                return false;
            }

            // check if bool arg supplied here to maintain BC
            if (is_array($allowedAlgorithms) && !in_array($header['alg'], $allowedAlgorithms)) {
                return false;
            }

            if (!$this->verifySignature($sig, "$headb64.$payloadb64", $key, $header['alg'])) {
                return false;
            }
        }

        return $payload;
    }

    private function verifySignature($signature, $input, $key, $algorithm = 'RS256')
    {
        switch ($algorithm) {
            case 'RS256':
                return openssl_verify($input, $signature, $key, defined('OPENSSL_ALGO_SHA256') ? OPENSSL_ALGO_SHA256 : 'sha256') === 1;
            case 'RS384':
                return @openssl_verify($input, $signature, $key, defined('OPENSSL_ALGO_SHA384') ? OPENSSL_ALGO_SHA384 : 'sha384') === 1;
            case 'RS512':
                return @openssl_verify($input, $signature, $key, defined('OPENSSL_ALGO_SHA512') ? OPENSSL_ALGO_SHA512 : 'sha512') === 1;
            default:
                throw new \InvalidArgumentException("Unsupported or invalid signing algorithm.");
        }
    }

    private function sign($input, $key, $algorithm = 'RS256')
    {
        switch ($algorithm) {
            case 'RS256':
                return $this->generateRSASignature($input, $key, defined('OPENSSL_ALGO_SHA256') ? OPENSSL_ALGO_SHA256 : 'sha256');
            case 'RS384':
                return $this->generateRSASignature($input, $key, defined('OPENSSL_ALGO_SHA384') ? OPENSSL_ALGO_SHA384 : 'sha384');
            case 'RS512':
                return $this->generateRSASignature($input, $key, defined('OPENSSL_ALGO_SHA512') ? OPENSSL_ALGO_SHA512 : 'sha512');
            default:
                throw new \Exception("Unsupported or invalid signing algorithm.");
        }
    }

    private function generateRSASignature($input, $key, $algorithm)
    {
        if (!openssl_sign($input, $signature, $key, $algorithm)) {
            throw new \Exception("Unable to sign data. " .openssl_error_string());
        }

        return $signature;
    }

    protected function generateJwtHeader($payload, $algorithm)
    {
        return array(
            'typ' => 'JWT',
            'alg' => $algorithm,
        );
    }

    protected function hash_equals($a, $b)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }
        $diff = strlen($a) ^ strlen($b);
        for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $diff === 0;
    }
}
