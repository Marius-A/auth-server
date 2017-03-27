<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \Symfony\Component\Security\Core\Role\Role as BaseRole;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class User
 * @package AppBundle
 * @ORM\Table(name="role")
 * @ORM\HasLifecycleCallbacks
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RoleRepository")
 */
class Role extends BaseRole
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", nullable=false)
     */
    protected $role;

    /**
     * @var
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true)
     */
    protected $lft;

    /**
     * @var
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     */
    protected $lvl;

    /**
     * @var
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     */
    protected $rgt;

    /**
     * @var
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    protected $root;

    /**
     * @var Role
     *
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Role", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     */
    protected $parent;

    protected $parentIdAlias;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Role", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    protected $children;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on = "create")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", nullable=false)
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
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\User", mappedBy="roles", fetch="EXTRA_LAZY")
     */
    protected $users;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Client", mappedBy="roles", fetch="EXTRA_LAZY")
     */
    protected $clients;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Client", mappedBy="requiredRoles", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(
     *      name="client_required_role"
     * )
     */
    protected $requiredClients;

    /**
     * Constructor.
     *
     * @param string $role The role name
     */
    public function __construct($role)
    {
        parent::__construct($role);
        $this->clients = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->role = (string)$role;
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
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
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getParentId()
    {
        if (is_null($this->parent)) {
            return null;
        }
        return $this->parent->getId();
    }

    /**
     * @return Collection
     */
    public function getClients()
    {
        return (is_null($this->clients)) ? new ArrayCollection() : $this->clients;
    }

    /**
     * @param Collection $clients
     */
    public function setClients(Collection $clients)
    {
        $this->clients = $clients;
    }

    /**
     * @return Role
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Role $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return array | ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param array $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
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
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @return mixed
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * @return mixed
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }
}
