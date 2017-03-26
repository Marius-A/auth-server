<?php

namespace AppBundle\OpenID\Encryption;

class Jwk
{
    /** @var  string */
    protected $kid;

    /** @var  string */
    protected $kty;

    /** @var  string */
    protected $alg;

    /** @var  string */
    protected $use;

    /** @var  string */
    protected $n;

    /** @var  string */
    protected $e;

    /**
     * @return string
     */
    public function getKid()
    {
        return $this->kid;
    }

    /**
     * @param string $kid
     */
    public function setKid($kid)
    {
        $this->kid = $kid;
    }

    /**
     * @return string
     */
    public function getKty()
    {
        return $this->kty;
    }

    /**
     * @param string $kty
     */
    public function setKty($kty)
    {
        $this->kty = $kty;
    }

    /**
     * @return string
     */
    public function getAlg()
    {
        return $this->alg;
    }

    /**
     * @param string $alg
     */
    public function setAlg($alg)
    {
        $this->alg = $alg;
    }

    /**
     * @return string
     */
    public function getUse()
    {
        return $this->use;
    }

    /**
     * @param string $use
     */
    public function setUse($use)
    {
        $this->use = $use;
    }

    /**
     * @return string
     */
    public function getN()
    {
        return $this->n;
    }

    /**
     * @param string $n
     */
    public function setN($n)
    {
        $this->n = $n;
    }

    /**
     * @return string
     */
    public function getE()
    {
        return $this->e;
    }

    /**
     * @param string $e
     */
    public function setE($e)
    {
        $this->e = $e;
    }
}