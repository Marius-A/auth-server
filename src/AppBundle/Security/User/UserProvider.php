<?php

namespace AppBundle\Security\User;

use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthProviderIdentifier;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class UserProvider implements UserProviderInterface
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Load user by username. Search also in LDAP. If username contains @, it will search by email
     *
     * @param string $username
     * @return UserInterface
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
        $manager = $this->doctrine->getManager();
        /** @var UserAuthProviderIdentifier $userAuthProviderIdentifier */
        $userAuthProviderIdentifier = $manager->getRepository('AppBundle:UserAuthProviderIdentifier')->findOneBy(array('identifier' => $username));

        if ($userAuthProviderIdentifier instanceof UserAuthProviderIdentifier) {
            $user = $userAuthProviderIdentifier->getUserAuthProvider()->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === 'AppBundle\User';
    }
}
