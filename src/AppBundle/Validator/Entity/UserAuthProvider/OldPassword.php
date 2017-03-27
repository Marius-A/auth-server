<?php

namespace AppBundle\Validator\Entity\UserAuthProvider;

use AppBundle\Validator\OauthConstraint;

/**
 * @Annotation
 */
class OldPassword extends OauthConstraint
{
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'encoder_factory_aware_old_password_validator';
    }
}
