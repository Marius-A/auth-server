<?php

namespace AppBundle\Validator\Entity\UserAuthProvider;

use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthProvider;
use AppBundle\Validator\OauthConstraint;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OldPasswordValidator extends ConstraintValidator
{
    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /** @var  EncoderFactory */
    protected $encoderFactory;

    /**
     * @param EncoderFactory $encoderFactory
     */
    public function setEncoderFactory($encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function validate($userAuthProvider, Constraint $constraint)
    {
        if (!$userAuthProvider instanceof UserAuthProvider) {
            return;
        }

        $violationCode = null;
        if ($constraint instanceof OauthConstraint) {
            $violationCode = $constraint->getCode();
        }

        $oldPassword = $userAuthProvider->getOldPassword();
        $oldPasswordAliasPasswordChange = $userAuthProvider->getOldPasswordAliasPasswordChange();

        if (!empty($oldPassword) && empty($oldPasswordAliasPasswordChange)) {
            $this->context->buildViolation("Old password is invalid")->setCode($violationCode)->addViolation();
            return;
        }

        if (!empty($oldPassword) && !$this->encoderFactory->getEncoder($userAuthProvider->getUser())->isPasswordValid($oldPassword, $oldPasswordAliasPasswordChange, null)) {
            $this->context->buildViolation("Old password is invalid")->setCode($violationCode)->addViolation();
            return;
        }
    }
}
