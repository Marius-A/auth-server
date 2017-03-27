<?php

namespace AppBundle\Controller\Annotations;

use AppBundle\Validator\NotBlankIfNotNull;
use FOS\RestBundle\Controller\Annotations as FOS;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Represents a parameter that must be present in POST data.
 *
 * @Annotation
 * @Target("METHOD")
 */
class RequestParam extends FOS\RequestParam
{
    /** @var  string */
    public $exceptionCode;

    /** @var  array */
    public $grantTypes;

    public function getConstraints()
    {
        $constraints = array();
        if (false === $this->allowBlank) {
            $constraints[] = new NotBlankIfNotNull();
            $this->allowBlank = true;
        }
        return array_merge($constraints,parent::getConstraints());
    }
}