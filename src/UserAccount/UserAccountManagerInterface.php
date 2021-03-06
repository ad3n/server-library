<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\UserAccount;

interface UserAccountManagerInterface
{
    /**
     * Check if the end-user password is valid.
     *
     * @param \OAuth2\UserAccount\UserAccountInterface $user     The end-user
     * @param string                                   $password Password
     *
     * @return bool
     */
    public function checkUserAccountPasswordCredentials(UserAccountInterface $user, $password);

    /**
     * Get the end-user with the specified User Account Name.
     *
     * @param string $username User Account Name
     *
     * @return \OAuth2\UserAccount\UserAccountInterface|null
     */
    public function getUserAccountByUsername($username);

    /**
     * Get the end-user with the specified public ID.
     *
     * @param string $public_id Public ID
     *
     * @return \OAuth2\UserAccount\UserAccountInterface|null
     */
    public function getUserAccountByPublicId($public_id);

    /**
     * Get the end-user with the specified username.
     *
     * @param string $resource Resource
     *
     * @return \OAuth2\UserAccount\UserAccountInterface|null
     */
    public function getUserAccountFromResource($resource);
}
