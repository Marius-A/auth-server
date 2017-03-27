<?php

namespace AppBundle\OpenID\Storage;

use Doctrine\Bundle\DoctrineBundle\Registry;

class UserClaimsDoctrineAdapter implements UserClaimsInterface
{
    const ADAPTER_NAME = 'openid.storage.user_claims_doctrine.adapter';
    /** @var  Registry */
    protected $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /** {@inheritdoc} */
    public function getUserClaims($userId, $scope = null)
    {
        $userClaims = array();
        if (empty($userId)) {
            return $userClaims;
        }

        $user = $this->doctrine->getManager()->getRepository('AppBundle:User')->find($userId);
        if (empty($user)) {
            return $userClaims;
        }

        $userDetails = [
            'email' => $user->getEmail(),
            'preferred_username' => $user->getUsername(),
            'scope' => $scope,
            'name' => NULL
        ];

        $claims = explode(' ', trim($scope));

        // for each requested claim, if the user has the claim, set it in the response
        $validClaims = explode(' ', self::VALID_CLAIMS);
        foreach ($validClaims as $validClaim) {
            if (in_array($validClaim, $claims)) {
                $userClaims = array_merge($userClaims, $this->getUserClaim($validClaim, $userDetails));
            }
        }

        return $userClaims;
    }

    protected function getUserClaim($claim, $userDetails)
    {
        $userClaims = array();
        $claimValuesString = constant(sprintf('self::%s_CLAIM_VALUES', strtoupper($claim)));
        $claimValues = explode(' ', $claimValuesString);

        foreach ($claimValues as $value) {
            $userClaims[$value] = isset($userDetails[$value]) ? $userDetails[$value] : null;
        }

        return $userClaims;
    }
}
