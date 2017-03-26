<?php

namespace AppBundle\Controller;

use AppBundle\OpenID\Encryption\Jwk;
use AppBundle\OpenID\Encryption\JwkContainer;
use AppBundle\Utils\UrlUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\NotImplementedException;
use FOS\RestBundle\Controller\Annotations as FOS;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class JwkController extends RestController
{
    /**
     * @FOS\View()
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns JWK tokens",
     *  section="Token",
     *  statusCodes={
     *         200="Returned when successful"
     *     }
     * )
     *
     * @FOS\Get("/oauth/v2/jwks")
     *
     * @return Response
     */
    public function getAction()
    {
        $rsaPublicKey = openssl_get_publickey($this->getParameter('jwt.cert'));
        $rsaPublicKeyDetails = openssl_pkey_get_details($rsaPublicKey);

        $jwk = new Jwk();
        $jwk->setKty($this->getParameter('jwt.kty'));
        $jwk->setAlg($this->getParameter('jwt.alg'));
        $jwk->setKid("jwk1");
        $jwk->setUse("sig");
        $jwk->setN(UrlUtil::urlSafeB64Encode($rsaPublicKeyDetails['rsa']['n']));
        $jwk->setE(UrlUtil::urlSafeB64Encode($rsaPublicKeyDetails['rsa']['e']));

        return new JwkContainer(array($jwk));
    }

    protected function getHandler()
    {
        throw new NotImplementedException("Not implemented");
    }
}