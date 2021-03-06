<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Stub;

use OAuth2\UserAccount\UserAccountInterface;
use OAuth2\UserAccount\UserAccountManagerInterface;

class UserAccountManager implements UserAccountManagerInterface
{
    /**
     * @var \OAuth2\UserAccount\UserAccountInterface[]
     */
    private $user_accounts = [];

    public function __construct()
    {
        $user1 = new UserAccount('user1', 'password1', 'real_user1_public_id');
        $user1->set('address', [
            'street_address' => '5 rue Sainte Anne',
            'region'         => 'Île de France',
            'postal_code'    => '75001',
            'locality'       => 'Paris',
            'country'        => 'France',
        ]);
        $user1->set('name', 'John Doe');
        $user1->set('given_name', 'John');
        $user1->set('family_name', 'Doe');
        $user1->set('middle_name', 'Jack');
        $user1->set('nickname', 'Little John');
        $user1->set('profile', 'https://profile.doe.fr/john/');
        $user1->set('preferred_username', 'j-d');
        $user1->set('gender', 'M');
        $user1->set('phone_number', '+0123456789');
        $user1->set('phone_number_verified', true);
        $user1->set('updated_at', time() - 1000);
        $user1->set('zoneinfo', 'Europe/Paris');
        $user1->set('locale', 'en');
        $user1->set('picture', 'https://www.google.com');
        $user1->set('amr', ['password', 'otp']);
        $user1->set('birthdate', '1950-01-01');
        $user1->set('email', 'root@localhost.com');
        $user1->set('email_verified', false);
        $user1->set('last_login_at', time() - 100);
        $user1->set('website', 'https://john.doe.com');
        $user1->set('website#fr_fr', 'https://john.doe.fr');
        $user1->set('website#fr', 'https://john.doe.fr');
        $user1->set('picture#de', 'https://john.doe.de/picture');

        $user2 = new UserAccount('user2', 'password2', 'real_user2_public_id');
        $user2->set('last_login_at', time() - 1000);

        // This account belongs to the real_user1_public_id.
        // Claims are not identical

        $user3 = new UserAccount('user3', 'password1', 'real_user1_public_id');
        $user3->set('name', 'John Doe');
        $user3->set('given_name', 'John');
        $user3->set('family_name', 'Doe');
        $user3->set('middle_name', 'Jack');
        $user3->set('nickname', 'Little John');
        $user3->set('profile', 'https://profile2.doe.fr/john/');
        $user3->set('preferred_username', 'j-d');
        $user3->set('gender', 'M');
        $user3->set('updated_at', time() - 1000);
        $user3->set('locale', 'en');
        $user3->set('amr', ['otp', 'eye']);
        $user3->set('email', 'root@test.org');
        $user3->set('email_verified', false);
        $user3->set('last_login_at', time() - 100);

        $this->user_accounts['user1'] = $user1;
        $this->user_accounts['user2'] = $user2;
        $this->user_accounts['user3'] = $user3;
    }

    /**
     * {@inheritdoc}
     */
    public function checkUserAccountPasswordCredentials(UserAccountInterface $resource_owner, $password)
    {
        if (!$resource_owner instanceof UserAccount) {
            return false;
        }

        return hash_equals($password, $resource_owner->getPassword());
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAccountByUsername($username)
    {
        return $this->getUserAccountByPublicId($username);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAccountByPublicId($public_id)
    {
        return isset($this->user_accounts[$public_id]) ? $this->user_accounts[$public_id] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAccountFromResource($resource)
    {
        $server = 'my-service.com:9000';
        $length = mb_strlen($server, 'utf-8');
        if ('https://'.$server.'/+' === mb_substr($resource, 0, $length + 10, 'utf-8')) {
            $resource = mb_substr($resource, $length + 10, null, 'utf-8');
        }
        if ('acct:' === mb_substr($resource, 0, 5, 'utf-8') && '@'.$server === mb_substr($resource, -($length + 1), null, 'utf-8')) {
            $resource = mb_substr($resource, 5, -($length + 1), 'utf-8');
        }

        return isset($this->user_accounts[$resource]) ? $this->user_accounts[$resource] : null;
    }
}
