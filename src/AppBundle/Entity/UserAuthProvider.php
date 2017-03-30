<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Validator\Entity\UserAuthProvider as UserAuthProviderAssert;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="user_auth_provider",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="user_auth_provider_unique",columns={"user_id","auth_provider_id"})}
 * )
 * @UserAuthProviderAssert\OldPassword(
 *      code="user.old_password.invalid",
 *      groups={AppBundle\Service\RoleService::GROUP_OLD_PASSWORD}
 * )
 * @UserAuthProviderAssert\ConfirmPassword(
 *      code="user.password_and_confirm_password.invalid_match",
 *      groups={AppBundle\Service\RoleService::GROUP_USER_PASSWORD_CHANGE}
 * )
 */
class UserAuthProvider
{
    const DEFAULT_PASSWORD_ENCODER = 'main';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var AuthProvider
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AuthProvider")
     * @ORM\JoinColumn(name="auth_provider_id", referencedColumnName="id", nullable=false)
     */
    protected $authProvider;

    /**
     * @var
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\UserAuthProviderIdentifier", mappedBy="userAuthProvider", fetch="EXTRA_LAZY", cascade={"all"})
     */
    protected $userAuthProviderIdentifiers;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", nullable=true, length=255)
     */
    protected $password;

    /**
     * @var string
     */
    protected $newPassword;

    /**
     * @var string
     */
    protected $confirmPassword;

    /**
     * @var string
     */
    protected $oldPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="encoder_name", type="string", length=50, nullable=true)
     */
    protected $encoderName;

    /**
     * @var string
     */
    protected $oldPasswordAliasPasswordChange;

    /**
     * @var string
     */
    protected $identifierAliasUserAuthProviderAdd;

    /**
     * @var
     */
    protected $idSetAlias;

    /**
     * @var boolean
     */
    protected $active;

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
     * @return AuthProvider
     */
    public function getAuthProvider()
    {
        return $this->authProvider;
    }

    /**
     * @param AuthProvider $authProvider
     */
    public function setAuthProvider($authProvider)
    {
        $this->authProvider = $authProvider;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return Collection
     */
    public function getUserAuthProviderIdentifiers()
    {
        if (is_null($this->userAuthProviderIdentifiers)) {
            $this->userAuthProviderIdentifiers = new ArrayCollection();
        }
        return $this->userAuthProviderIdentifiers;
    }

    /**
     * @param Collection $userAuthProviderIdentifiers
     */
    public function setUserAuthProviderIdentifiers(Collection $userAuthProviderIdentifiers)
    {
        $this->userAuthProviderIdentifiers = $userAuthProviderIdentifiers;
    }

    /**
     * @return string
     */
    public function getConfirmPassword()
    {
        return $this->confirmPassword;
    }

    /**
     * @param string $confirmPassword
     */
    public function setConfirmPassword($confirmPassword)
    {
        $this->confirmPassword = $confirmPassword;
    }

    /**
     * @return string
     */
    public function getNewPassword()
    {
        return $this->newPassword;
    }

    /**
     * @param string $newPassword
     */
    public function setNewPassword($newPassword)
    {
        $this->newPassword = $newPassword;
    }

    /**
     * @return string
     */
    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    /**
     * @param string $oldPassword
     */
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;
    }

    /**
     * @param bool $returnDefault
     * @return string
     */
    public function getEncoderName($returnDefault = true)
    {
        return ($this->encoderName || !$returnDefault) ? $this->encoderName : static::DEFAULT_PASSWORD_ENCODER;
    }

    /**
     * @param string $encoderName
     */
    public function setEncoderName($encoderName)
    {
        $this->encoderName = $encoderName;
    }

    /**
     * @param string $identifierAliasUserAuthProviderAdd
     */
    public function setIdentifierAliasUserAuthProviderAdd($identifierAliasUserAuthProviderAdd)
    {
        $this->identifierAliasUserAuthProviderAdd = $identifierAliasUserAuthProviderAdd;
    }

    /**
     * @return string
     */
    public function getIdentifierAliasUserAuthProviderAdd()
    {
        return $this->identifierAliasUserAuthProviderAdd;
    }

    /**
     * @return string
     */
    public function getOldPasswordAliasPasswordChange()
    {
        return $this->oldPasswordAliasPasswordChange;
    }

    /**
     * @param string $oldPasswordAliasPasswordChange
     */
    public function setOldPasswordAliasPasswordChange($oldPasswordAliasPasswordChange)
    {
        $this->oldPasswordAliasPasswordChange = $oldPasswordAliasPasswordChange;
    }

    /**
     * Check if user auth provider is active
     *
     * @return bool
     */
    public function isActive()
    {
        if (empty($this->authProvider)) {
            return false;
        }
        if ($this->authProvider->getId() == AuthProvider::MAIN_PROVIDER_ID && empty($this->password)) {
            return false;
        }

        return true;
    }
}