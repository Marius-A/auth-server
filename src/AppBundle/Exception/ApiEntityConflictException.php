<?php

namespace AppBundle\Exception;

class ApiEntityConflictException extends ApiException
{
    protected $conflictEntity = null;

    /**
     * @param string $messages
     * @param int $code
     * @param null $previous
     * @param null $conflictEntity
     */
    public function __construct($messages, $code = 0, $previous = null, $conflictEntity = null)
    {
        parent::__construct($messages, $code, $previous);

        $this->setConflictEntity($conflictEntity);
    }

    /**
     * @return null
     */
    public function getConflictEntity()
    {
        return $this->conflictEntity;
    }

    /**
     * @param null $conflictEntity
     */
    public function setConflictEntity($conflictEntity)
    {
        $this->conflictEntity = $conflictEntity;
    }
}
