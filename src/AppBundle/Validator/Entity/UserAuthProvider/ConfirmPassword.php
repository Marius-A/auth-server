<?php

namespace AppBundle\Validator\Entity\UserAuthProvider;

use AppBundle\Validator\OauthConstraint;

/**
 * @Annotation
 */
class ConfirmPassword extends OauthConstraint
{
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'encoder_factory_aware_confirm_password_validator';
    }
}
