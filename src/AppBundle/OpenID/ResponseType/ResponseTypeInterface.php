<?php

namespace AppBundle\OpenID\ResponseType;

interface ResponseTypeInterface
{
    public function getAuthorizeResponse($params, $userId = null);
}
