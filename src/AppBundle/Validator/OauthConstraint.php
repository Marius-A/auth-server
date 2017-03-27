<?php

namespace AppBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OauthConstraint extends Constraint
{
    /**
     * @var string
     */
    public $code = null;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
}