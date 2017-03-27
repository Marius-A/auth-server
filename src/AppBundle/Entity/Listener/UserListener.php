<?php

namespace AppBundle\Entity\Listener;

use AppBundle\Entity\AuthProvider;
use AppBundle\Entity\DynamicRole\UserIdPasswordChangeRole;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthProvider;
use AppBundle\Entity\UserAuthProviderIdentifier;
use AppBundle\Service\FlushedEntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

class UserListener
{
    /** @var  Registry */
    protected $doctrine;

    /** @var  EncoderFactory */
    protected $encoderFactory;

    /** @var  FlushedEntityManager */
    protected $flushedEntityManager;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine(Registry $doctrine)
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
     * @param FlushedEntityManager $flushedEntityManager
     */
    public function setFlushedEntityManager($flushedEntityManager)
    {
        $this->flushedEntityManager = $flushedEntityManager;
    }

    public function prePersist(User $user, LifecycleEventArgs $event)
    {
        # CREATE USER AUTH PROVIDER FOR MAIN PROVIDER
        $this->createMainProvider($user);

        # CREATE IDENTIFIERS FOR MAIN PROVIDER
        $this->createMainProviderIdentifiers($user);

        # APPLY CLIENT DEFAULT ROLE TEMPLATE (IF ANY)
        $this->applyClientDefaultRoleTemplateOnUser($user);
    }

    private function setUsernameOnUserAndEvent($username, User $user, PreUpdateEventArgs $event)
    {
        $user->setUsername($username);
        $event->setNewValue('username', $username);
    }

    public function postUpdate(User $user, LifecycleEventArgs $args)
    {
        $this->flushedEntityManager->markAsUpdated($user);
    }

    public function postPersist(User $user, LifecycleEventArgs $args)
    {
        $this->setDynamicRolesOnUser($user);
    }

    /**
     * @param User $user
     * @param LifecycleEventArgs $event
     */
    public function postLoad(User $user, LifecycleEventArgs $event)
    {
        $this->setDynamicRolesOnUser($user);
    }

    /**
     * @param User $user
     */
    protected function applyClientDefaultRoleTemplateOnUser(User $user)
    {
        $clientId = $user->getRequestingClientId();
        if (empty($clientId)) {
            return;
        }

        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $client = $manager->getRepository('AppBundle:Client')->find($clientId);
        if (empty($client)) {
            return;
        }
    }

    /**
     * @param User $user
     */
    protected function createMainProvider(User $user)
    {
        $manager = $this->doctrine->getManager();

        /** @var AuthProvider $authProvider */
        $authProvider = $manager->getRepository('AppBundle:AuthProvider')->find(AuthProvider::MAIN_PROVIDER_ID);

        $userAuthProvider = new UserAuthProvider();

        $userAuthProvider->setAuthProvider($authProvider);
        $userAuthProvider->setUser($user);
        $this->doctrine->getManager()->persist($userAuthProvider);
        $userAuthProviders = $user->getUserAuthProviders();
        $userAuthProviders->add($userAuthProvider);
    }

    /**
     * @param User $user
     */
    protected function createMainProviderIdentifiers(User $user)
    {
        $userAuthProvider = $user->getUserAuthProvider(AuthProvider::MAIN_PROVIDER_ID);
        if (!($userAuthProvider instanceof UserAuthProvider)) {
            return;
        }

        $username = $user->getUsername();
        $email = $user->getEmail();

        $this->createUserAuthProviderIdentifier($username, $userAuthProvider);
        $this->createUserAuthProviderIdentifier($email, $userAuthProvider);
    }

    /**
     * @param $identifier
     * @param UserAuthProvider $userAuthProvider
     */
    protected function createUserAuthProviderIdentifier($identifier, UserAuthProvider $userAuthProvider)
    {
        if (empty($identifier)) {
            return;
        }

        $entityDeletions = $this->doctrine->getManager()->getUnitOfWork()->getScheduledEntityDeletions();
        $foundIdentifier = false;
        foreach($entityDeletions as $entity) {
            if ($entity instanceof UserAuthProviderIdentifier) {
                if ($entity->getUserAuthProvider() == $userAuthProvider && $entity->getIdentifier() ==  $identifier) {
                    $this->persistAndAddToProvider($entity, $userAuthProvider);
                    $foundIdentifier = true;
                }
            }
        }

        if (!$foundIdentifier) {
            $userAuthProviderIdentifier = new UserAuthProviderIdentifier();
            $userAuthProviderIdentifier->setIdentifier($identifier);
            $userAuthProviderIdentifier->setUserAuthProvider($userAuthProvider);

            $this->persistAndAddToProvider($userAuthProviderIdentifier, $userAuthProvider);
        }
    }

    private function persistAndAddToProvider(UserAuthProviderIdentifier $identifier, UserAuthProvider $userAuthProvider)
    {
        $this->doctrine->getManager()->persist($identifier);
        $userAuthProvider->getUserAuthProviderIdentifiers()->add($identifier);
    }

    /**
     * @param User $user
     */
    public function updateMainProviderIdentifiers(User $user)
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManager();
        $classMetadata = $manager->getClassMetadata("AppBundle\\Entity\\UserAuthProvider");

        $userAuthProvider = $user->getUserAuthProvider(AuthProvider::MAIN_PROVIDER_ID);
        if (!($userAuthProvider instanceof UserAuthProvider)) {
            return;
        }

        $userAuthProviderIdentifiers = $userAuthProvider->getUserAuthProviderIdentifiers();
        foreach ($userAuthProviderIdentifiers as $userAuthProviderIdentifier) {
            $manager->remove($userAuthProviderIdentifier);
        }
        $userAuthProviderIdentifiers->clear();

        $manager->getUnitOfWork()->computeChangeSet($classMetadata, $userAuthProvider);

        $this->createMainProviderIdentifiers($user);
    }

    /**
     * @param User $user
     */
    protected function setDynamicRolesOnUser(User $user)
    {
        $user->setDynamicRoles(
            new ArrayCollection(
                array(
                    new UserIdPasswordChangeRole($user)
                )
            )
        );
    }
}
