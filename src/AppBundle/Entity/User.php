<?php

namespace AppBundle\Entity;

use AppBundle\Event\Interfaces\RemoveEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\EntityListeners({"AppBundle\Entity\Listener\UserListener"})
 */
class User implements UserInterface, RemoveEntityInterface, EncoderAwareInterface
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true, unique=true)
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="email", length=255, nullable=true, unique=true)
     */
    protected $email;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on = "create")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on = "update")
     */
    protected $modified;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = 1;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Role")
     */
    protected $roles;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\RoleTemplate", fetch="EXTRA_LAZY", cascade={"all"})
     * @ORM\JoinTable(
     *      name="user_role_template",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_template_id", referencedColumnName="id")}
     * )
     */
    protected $roleTemplates;

    /**
     * @var array
     */
    protected $dynamicRoles;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserAuthProvider", mappedBy="user", fetch="EXTRA_LAZY", cascade={"all"})
     */
    protected $userAuthProviders;

    /**
     * @var int
     */
    protected $idAliasMe;

    /**
     * @var integer
     */
    protected $idSetAlias;

    /**
     * @var string
     */
    protected $emailSetAlias;

    /**
     * @var string
     */
    protected $usernameSetAlias;


    /**
     * @var string
     *
     * Caution! This has a default value that cannot be a real password
     */
    protected $password = 0;

    /**
     * @var string
     */
    protected $encoderName;

    /**
     * @var boolean
     */
    protected $statusSetAlias;

    /**
     * @var integer
     */
    protected $requestingClientId = null;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->dynamicRoles = new ArrayCollection();
        $this->roleTemplates = new ArrayCollection();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param \DateTime $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

    /**
     * @return string
     *
     * Returns password for default provider
     */
    public function getPassword()
    {
        $userAuthProvider = $this->getUserAuthProvider(AuthProvider::MAIN_PROVIDER_ID);
        if (is_null($userAuthProvider) || ($userAuthProvider instanceof UserAuthProvider && is_null($userAuthProvider->getPassword()))) {
            return ""; // not recommended to return null because will issue polyfill warnings
        }
        return $userAuthProvider->getPassword();
    }

    /**
     * @return string
     */
    public function getPasswordSet()
    {
        if (!$this->isPasswordSet()) {
            return null;
        }
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isPasswordSet()
    {
        return $this->password !== 0;
    }

    /**
     * @param $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEncoderName()
    {
        $userAuthProvider = $this->getUserAuthProvider(AuthProvider::MAIN_PROVIDER_ID);
        if (is_null($userAuthProvider)) {
            return $this->encoderName ?: UserAuthProvider::DEFAULT_PASSWORD_ENCODER;
        }
        return $userAuthProvider->getEncoderName();
    }

    /**
     * @param string $encoderName
     */
    public function setEncoderName($encoderName)
    {
        $this->encoderName = $encoderName;
    }

    /**
     * Return user roles
     *
     * Caution! This getter on OneToMany relations returns array by default in order to work with
     * security layer. This strategy should be avoided and returned Collection instead
     * @param bool $asCollection
     * @return array|ArrayCollection
     */
    public function getRoles($asCollection = false)
    {
        if (!$asCollection) {
            if ($this->roles instanceof Collection) {
                return (is_null($this->roles)) ? array() : $this->roles->toArray();
            }
            return (is_null($this->roles)) ? array() : $this->roles;
        }
        return (is_null($this->roles)) ? new ArrayCollection() : $this->roles;
    }

    /**
     * @param Collection $roles
     */
    public function setRoles(Collection $roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return Collection
     */
    public function getDynamicRoles()
    {
        return (is_null($this->dynamicRoles)) ? new ArrayCollection() : $this->dynamicRoles;
    }

    /**
     * @param Collection $dynamicRoles
     */
    public function setDynamicRoles(Collection $dynamicRoles)
    {
        $this->dynamicRoles = $dynamicRoles;
    }

    /**
     * @return Collection
     */
    public function getUserAuthProviders()
    {
        if (is_null($this->userAuthProviders)) {
            $this->userAuthProviders = new ArrayCollection();
        }
        return $this->userAuthProviders;
    }

    /**
     * Get user auth provider entity for a given auth provider id
     *
     * @param $authProviderId
     * @return UserAuthProvider|mixed
     */
    public function getUserAuthProvider($authProviderId)
    {
        $userAuthProviders = $this->getUserAuthProviders();
        foreach ($userAuthProviders as $userAuthProvider) {
            /** @var UserAuthProvider $userAuthProvider */
            $authProvider = $userAuthProvider->getAuthProvider();
            if (!is_null($authProvider)) {
                if ($userAuthProvider->getAuthProvider()->getId() == $authProviderId) {
                    return $userAuthProvider;
                }
            }

        }
        return null;
    }

    /**
     * @param Collection $userAuthProviders
     */
    public function setUserAuthProviders(Collection $userAuthProviders)
    {
        $this->userAuthProviders = $userAuthProviders;
    }

    /**
     * @return boolean
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return Collection
     */
    public function getRoleTemplates()
    {
        return (is_null($this->roleTemplates)) ? new ArrayCollection() : $this->roleTemplates;
    }

    /**
     * @param Collection $roleTemplates
     */
    public function setRoleTemplates(Collection $roleTemplates)
    {
        $this->roleTemplates = $roleTemplates;
    }

    /**
     * @return integer
     */
    public function getRequestingClientId()
    {
        return $this->requestingClientId;
    }

    /**
     * @param integer $requestingClientId
     */
    public function setRequestingClientId($requestingClientId)
    {
        $this->requestingClientId = $requestingClientId;
    }

    public function remove()
    {
        $this->setStatus(self::STATUS_INACTIVE);
    }
}