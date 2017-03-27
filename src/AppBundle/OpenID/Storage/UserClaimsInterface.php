<?php

namespace AppBundle\OpenID\Storage;

interface UserClaimsInterface
{
    // valid scope values to pass into the user claims API call
    const VALID_CLAIMS = 'profile email address phone profile';

    // fields returned for the claims above
    // standard claims
    const PROFILE_CLAIM_VALUES  = 'first_name name family_name given_name middle_name nickname preferred_username profile picture website gender birthdate zoneinfo locale updated_at email';
    const EMAIL_CLAIM_VALUES    = 'email email_verified';
    const ADDRESS_CLAIM_VALUES  = 'formatted street_address locality region postal_code country';
    const PHONE_CLAIM_VALUES    = 'phone_number phone_number_verified';

    /**
     * Return claims about the provided user id.
     *
     * Groups of claims are returned based on the requested scopes. No group
     * is required, and no claim is required.
     *
     * @param $userId
     * The id of the user for which claims should be returned.
     * @param $scope
     * The requested scope.
     * Scopes with matching claims: profile, email, address, phone.
     *
     * @return
     * An array in the claim => value format.
     */
    public function getUserClaims($userId, $scope);
}
