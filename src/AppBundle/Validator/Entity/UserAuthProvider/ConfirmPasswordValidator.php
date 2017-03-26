<?php

namespace AppBundle\Validator\Entity\UserAuthProvider;

use AppBundle\Entity\UserAuthProvider;
use AppBundle\Validator\OauthConstraint;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfirmPasswordValidator extends ConstraintValidator
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

        $newPassword = $userAuthProvider->getNewPassword();
        if (empty($newPassword)) {
            return;
        }

        $confirmPassword = $userAuthProvider->getConfirmPassword();
        if (empty($confirmPassword)) {
            return;
        }

        if (!$this->encoderFactory->getEncoder($userAuthProvider->getUser())->isPasswordValid($newPassword, $confirmPassword, null)) {
            $this->context->buildViolation("Password and confirmed password do not match")->setCode($violationCode)->addViolation();
            return;
        }
    }
}
