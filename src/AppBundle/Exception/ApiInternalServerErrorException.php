<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ApiInternalServerErrorException extends ApiException
{
    /**
     * @param string $messages
     * @param int $code
     * @param null $previous
     */
    public function __construct($messages = null, $code = null, $previous = null)
    {
        if (is_null($code)) {
            $code = Response::HTTP_INTERNAL_SERVER_ERROR;
        }
        if (is_null($messages)) {
            $messages = array(
                $code => "Internal server error"
            );
        }
        parent::__construct($messages, $code, $previous);
    }
}
