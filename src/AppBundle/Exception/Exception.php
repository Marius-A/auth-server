<?php

namespace AppBundle\Exception;

use OAuth2\OAuth2ServerException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class Exception extends \Exception
{
    protected $message = array();

    /**
     * @param $messages
     * @param int $code
     * @param null $previous
     */
    public function __construct($messages, $code = 0, $previous = null)
    {
        $this->message = array();

        if (is_array($messages)) {
            $this->message = $messages;
        } else
        if (is_string($messages)) {
            $this->message = array($messages);
        }

        parent::__construct($this->messageToString(), $code, $previous);
    }

    /**
     * Create exception from a list of violations
     *
     * @param ConstraintViolationListInterface $constraintViolationList
     * @return Exception
     */
    public static function createFromConstraintViolationList(ConstraintViolationListInterface $constraintViolationList)
    {
        $messages = array();
        foreach($constraintViolationList as $constraintViolation) {
            $code = $constraintViolation->getCode();
            if (!empty($code)) {
                $messages[$code] = $constraintViolation->getMessage();
            } else {
                $messages[] = $constraintViolation->getMessage();
            }
        }
        return new static($messages);
    }

    /**
     * Create exception from an OAuth2ServerException
     *
     * @param OAuth2ServerException $oAuth2ServerException
     * @return Exception
     */
    public static function createFromOauth2ServerException(OAuth2ServerException $oAuth2ServerException)
    {
        $httpResponse = json_decode($oAuth2ServerException->getResponseBody(), true);

        $messages = array();
        $code = !empty($httpResponse['error']) ? $httpResponse['error'] : 0;
        $messages[$code] = !empty($httpResponse['error_description']) ? $httpResponse['error_description'] : '';
        return new static($messages, $oAuth2ServerException->getHttpResponse()->getStatusCode(), $oAuth2ServerException);
    }

    /**
     * @param $message
     */
    public function addMessage($message)
    {
        $this->message[] = $message;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        $exceptionMessage = array();
        if (!empty($this->message)) {
            $decodedMessage = json_decode($this->message, true);
            if (!empty($decodedMessage)) {
                $exceptionMessage = $decodedMessage;
            }
        }
        return $exceptionMessage;
    }

    /**
     * @return string
     */
    private function messageToString()
    {
        return json_encode($this->message);
    }
}
