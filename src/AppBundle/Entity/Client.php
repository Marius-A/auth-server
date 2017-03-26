<?php

namespace AppBundle\Entity;

use AppBundle\Event\Interfaces\RemoveEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Gedmo\Mapping\Annotation as Gedmo;
use FOS\OAuthServerBundle\Util\Random;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ClientRepository")
 * @ORM\Table(name="client")
 * @ORM\HasLifecycleCallbacks()
 */
class Client extends BaseClient implements RemoveEntityInterface
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false, length=255)
     */
    protected $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = 1;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\RoleTemplate", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="default_role_template_id", referencedColumnName="id")
     */
    protected $defaultRoleTemplate;

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
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Role", fetch="EXTRA_LAZY")
     */
    protected $roles;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Role", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(
     *      name="client_required_role"
     * )
     */
    protected $requiredRoles;

    /**
     * @var integer
     */
    protected $idIntrospectAlias;

    /**
     * @var string
     */
    protected $secretAlias;

    /**
     * @var string
     */
    protected $randomIdAlias;

    /**
     * @var array
     */
    protected $allowedGrantTypesAlias;

    /**
     * @var array
     */
    protected $redirectUrisAlias;

    public function __construct()
    {
        $this->roles = new ArrayCollection();

        parent::__construct();
    }

    public function init()
    {
        $this->setRandomId(Random::generateToken());
        $this->setSecret(Random::generateToken());
    }

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="default_email", length=100, nullable=false)
     */
    protected $defaultEmail;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="default_encoder", length=20, nullable=true)
     */
    protected $defaultEncoder = null;

    /**
     * @var string
     *
     * @ORM\Column(name="password_reset_url", type="string", length=255, nullable=false)
     */
    protected $passwordResetUrl;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $clientUser;

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return integer
     */
    public function getName()
    {
        return $this->name;
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
     * @param bool $asCollection
     * @return array|ArrayCollection
     */
    public function getRoles($asCollection = true)
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
     * @param bool $asCollection
     * @return array|ArrayCollection
     */
    public function getRequiredRoles($asCollection = true)
    {
        if (!$asCollection) {
            if ($this->requiredRoles instanceof Collection) {
                return (is_null($this->requiredRoles)) ? array() : $this->requiredRoles->toArray();
            }
            return (is_null($this->requiredRoles)) ? array() : $this->requiredRoles;
        }
        return (is_null($this->requiredRoles)) ? new ArrayCollection() : $this->requiredRoles;
    }

    /**
     * @param Collection $requiredRoles
     */
    public function setRequiredRoles(Collection $requiredRoles)
    {
        $this->requiredRoles = $requiredRoles;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function remove()
    {
        $this->setStatus(self::STATUS_INACTIVE);
    }

    /**
     * @return string
     */
    public function getDefaultEmail()
    {
        return $this->defaultEmail;
    }

    /**
     * @param string $defaultEmail
     */
    public function setDefaultEmail($defaultEmail)
    {
        $this->defaultEmail = $defaultEmail;
    }

    /**
     * @return string
     */
    public function getDefaultEncoder()
    {
        return $this->defaultEncoder;
    }

    /**
     * @param string $defaultEncoder
     */
    public function setDefaultEncoder($defaultEncoder)
    {
        $this->defaultEncoder = $defaultEncoder;
    }

    /**
     * @return string
     */
    public function getPasswordResetUrl()
    {
        return $this->passwordResetUrl;
    }

    /**
     * @param string $passwordResetUrl
     */
    public function setPasswordResetUrl($passwordResetUrl)
    {
        $this->passwordResetUrl = $passwordResetUrl;
    }

    /**
     * @return mixed
     */
    public function getDefaultRoleTemplate()
    {
        return $this->defaultRoleTemplate;
    }

    /**
     * @param mixed $defaultRoleTemplate
     */
    public function setDefaultRoleTemplate($defaultRoleTemplate)
    {
        $this->defaultRoleTemplate = $defaultRoleTemplate;
    }

    /**
     * @return User
     */
    public function getClientUser()
    {
        return $this->clientUser;
    }

    /**
     * @param User $clientUser
     */
    public function setClientUser($clientUser)
    {
        $this->clientUser = $clientUser;
    }
}
