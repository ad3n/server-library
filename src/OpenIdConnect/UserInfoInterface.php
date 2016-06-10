<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\OpenIdConnect;

use OAuth2\Client\ClientInterface;
use OAuth2\OpenIdConnect\Pairwise\PairwiseSubjectIdentifierAlgorithmInterface;
use OAuth2\User\UserInterface;

interface UserInfoInterface
{
    /**
     * @param \OAuth2\OpenIdConnect\Pairwise\PairwiseSubjectIdentifierAlgorithmInterface $algorithm
     */
    public function enablePairwiseSubject(PairwiseSubjectIdentifierAlgorithmInterface $algorithm);

    /**
     * @return bool
     */
    public function isPairwiseSubjectIdentifierSupported();

    /**
     * @param \OAuth2\Client\ClientInterface $client
     * @param \OAuth2\User\UserInterface     $user
     * @param string                         $redirect_uri
     * @param array                          $request_claims
     * @param string[]                       $scope
     *
     * @return array
     */
    public function getUserinfo(ClientInterface $client, UserInterface $user, $redirect_uri, array $request_claims, array $scope);
}
