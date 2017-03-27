<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class RoleTemplate
 * @ORM\Table(name="role_template")
 * @ORM\Entity
 */
class RoleTemplate
{
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
     * @ORM\Column(name="role", type="string", nullable=false)
     */
    protected $description;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Role", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(
     *      name="role_template_role",
     *      joinColumns={@ORM\JoinColumn(name="role_template_id", referencedColumnName="id")}
     * )
     */
    protected $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return Collection
     */
    public function getRoles()
    {
        return (is_null($this->roles)) ? new ArrayCollection() : $this->roles;
    }

    /**
     * @param Collection $roles
     */
    public function setRoles(Collection $roles)
    {
        $this->roles = $roles;
    }
}
