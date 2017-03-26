<?php

namespace AppBundle\Helper;

use Symfony\Component\Validator\Constraints as Assert;

class Email
{
    /**
     * @var int
     * @Assert\NotBlank()
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\Email(
     *      message="To email address must be correct"
     * )
     * @Assert\NotBlank(
     *      message="To email address must be set"
     * )
     */
    private $to;

    /**
     * @var string
     *
     * @Assert\Email(
     *      message="To email address must be correct"
     * )
     * @Assert\NotBlank(
     *      message="To email address must be set"
     * )
     */
    private $from;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *      message="The email's body must be set"
     * )
     */
    private $body;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *      message="The subject must be set"
     * )
     */
    private $subject;

    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $paramsData;

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param $to
     * @return $this
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParamsData()
    {
        return $this->paramsData;
    }

    /**
     * @param array $paramsData
     */
    public function setParamsData($paramsData)
    {
        $this->paramsData = $paramsData;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
