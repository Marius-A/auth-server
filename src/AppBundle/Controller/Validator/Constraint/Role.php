<?php

namespace AppBundle\Controller\Validator\Constraint;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Role extends Constraint
{
    public $roles;
    public function validatedBy()
    {
        return 'app_bundle.controller.validator.role';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

