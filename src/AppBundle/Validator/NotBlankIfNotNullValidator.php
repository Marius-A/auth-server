<?php

namespace AppBundle\Validator;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlankValidator;

class NotBlankIfNotNullValidator extends NotBlankValidator
{

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotBlankIfNotNull) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\NotBlankIfNotNull');
        }

        if (is_null($value)) {
            return;
        }

        parent::validate($value, $constraint);
    }
}
