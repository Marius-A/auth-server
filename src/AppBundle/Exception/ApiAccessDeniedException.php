<?php

namespace AppBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

class ApiAccessDeniedException extends ApiException
{
    /**
     * @param string $messages
     * @param int $code
     * @param null $previous
     */
    public function __construct($messages = null, $code = null, $previous = null)
    {
        if (is_null($code)) {
            $code = Response::HTTP_FORBIDDEN;
        }
        if (is_null($messages)) {
            $messages = array(
                $code => "Access denied"
            );
        }
        parent::__construct($messages, $code, $previous);
    }
}
