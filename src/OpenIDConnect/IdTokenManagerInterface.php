<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\OpenIDConnect;

use OAuth2\Client\ClientInterface;
use OAuth2\EndUser\EndUserInterface;
use OAuth2\Token\AccessTokenInterface;
use OAuth2\Token\AuthCodeInterface;

interface IdTokenManagerInterface
{
    /**
     * @param \OAuth2\Client\ClientInterface          $client
     * @param \OAuth2\EndUser\EndUserInterface        $end_user
     * @param array                                   $token_type_information
     * @param array                                   $id_token_claims
     * @param \OAuth2\Token\AccessTokenInterface|null $access_token
     * @param \OAuth2\Token\AuthCodeInterface|null    $auth_code
     *
     * @return mixed
     */
    public function createIdToken(ClientInterface $client, EndUserInterface $end_user, array $token_type_information, array $id_token_claims = [], AccessTokenInterface $access_token = null, AuthCodeInterface $auth_code = null);

    /**
     * @param \OAuth2\OpenIDConnect\IdTokenInterface $token The ID token to revoke
     */
    public function revokeIdToken(IdTokenInterface $token);

    /**
     * @param string $id_token The ID token
     *
     * @return \OAuth2\OpenIDConnect\IdTokenInterface|null Return the ID token or null
     */
    public function getIdToken($id_token);
}