<?php

namespace AppBundle\Security\Encoder;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;

/**
 * MainPasswordEncoder uses a message digest algorithm.
 */
class MainPasswordEncoder extends BasePasswordEncoder
{
    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        if (!$salt) {
            $salt = substr(md5(microtime()), rand(0, 26), 2);
        }

        return md5($salt . $raw) . ':' . $salt;
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt = null)
    {
        if (!$salt) {
            $encodedSplit = explode(':', $encoded);
            $salt = isset($encodedSplit[1]) ? $encodedSplit[1] : null;
        }

        if (!$salt) {
            return false;
        }

        return !$this->isPasswordTooLong($raw) && $this->comparePasswords($encoded, $this->encodePassword($raw, $salt));
    }
}