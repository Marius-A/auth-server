<?php

namespace AppBundle\Exception;

use AppBundle\Exception\Interfaces\NonLoggableExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ApiException extends Exception implements HttpExceptionInterface, NonLoggableExceptionInterface
{
    /**
     * @param string $messages
     * @param int $code
     * @param null $previous
     */
    public function __construct($messages, $code = 0, $previous = null)
    {
        parent::__construct($messages, $code, $previous);

        $this->setStatusCode($code);
    }

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Returns response headers.
     *
     * @return array Response headers
     */
    public function getHeaders()
    {
        return array();
    }


    /**
     * @return array
     */
    public function getMessages()
    {
        $apiExceptionMessage = array();
        $exceptionMessage = parent::getMessages();
        if (!empty($exceptionMessage)) {
            $apiExceptionMessage['errors'] = $exceptionMessage;
        }
        return $apiExceptionMessage;
    }
}
