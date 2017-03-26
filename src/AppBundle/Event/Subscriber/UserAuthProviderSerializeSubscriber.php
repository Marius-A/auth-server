<?php

namespace AppBundle\Event\Subscriber;

use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthProvider;
use AppBundle\Entity\UserAuthProviderIdentifier;
use AppBundle\Service\RoleService;
use AppBundle\Service\UserAuthProviderService;
use AppBundle\Service\UserService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

class UserAuthProviderSerializeSubscriber implements EventSubscriberInterface
{
    /** @var  Registry */
    protected $doctrine;

    /** @var  EncoderFactory */
    protected $encoderFactory;

    /** @var  UserAuthProviderService */
    protected $userAuthProviderService;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param EncoderFactory $encoderFactory
     */
    public function setEncoderFactory($encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param UserAuthProviderService $userAuthProviderService
     */
    public function setUserAuthProviderService($userAuthProviderService)
    {
        $this->userAuthProviderService = $userAuthProviderService;
    }

    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => Events::POST_DESERIALIZE,
                'format' => 'json',
                'class' => 'AppBundle\\Entity\\UserAuthProvider',
                'method' => 'onPostDeserialize'
            )
        );
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostDeserialize(ObjectEvent $event)
    {
        $context = $event->getContext();
        $groups = $context->attributes->get('groups')->getOrElse(array());

        foreach ($groups as $group) {
            if ($group == RoleService::GROUP_USER_PASSWORD_CHANGE) {
                $this->onPostDeserializeUserAuthProviderPasswordChange($event);
            } elseif ($group == RoleService::ROLE_USER_AUTH_PROVIDER_ADD) {
                $this->onPostDeserializeUserAuthProviderAdd($event);
            }
        }
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostDeserializeUserAuthProviderPasswordChange(ObjectEvent $event)
    {
        /** @var UserAuthProvider $userAuthProvider */
        $userAuthProvider = $event->getObject();

        # OLD PASSWORD
        $oldPassword = $userAuthProvider->getPassword();
        if (!empty($oldPassword)) {
            $userAuthProvider->setOldPassword($oldPassword);
        }
        # NEW PASSWORD
        $newPassword = $userAuthProvider->getNewPassword();
        if (!empty($newPassword)) {
            $encodedNewPassword = $this->encoderFactory->getEncoder($userAuthProvider->getUser())->encodePassword($newPassword, null);
            $userAuthProvider->setPassword($encodedNewPassword);
            $userAuthProvider->setNewPassword($encodedNewPassword);
        }
        # getConfirmPassword() and getOldPasswordAliasPasswordChange() methods should return raw values
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostDeserializeUserAuthProviderAdd(ObjectEvent $event)
    {
        /** @var UserAuthProvider $userAuthProvider */
        $userAuthProvider = $event->getObject();

        #IDENTIFIER
        $identifier = $userAuthProvider->getIdentifierAliasUserAuthProviderAdd();
        if (!empty(($identifier))) {
            $userAuthProviderIdentifier = new UserAuthProviderIdentifier();
            $userAuthProviderIdentifier->setUserAuthProvider($userAuthProvider);
            $userAuthProviderIdentifier->setIdentifier($identifier);

            $this->doctrine->getManager()->persist($userAuthProviderIdentifier);
        }
    }
}