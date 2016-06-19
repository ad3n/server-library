<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Endpoint\Authorization\PreConfiguredAuthorization;

interface PreConfiguredAuthorizationInterface
{
    /**
     * @return string
     */
    public function getResourceOwnerPublicId();

    /**
     * @param string $resource_owner_public_id
     */
    public function setResourceOwnerPublicId($resource_owner_public_id);
    
    /**
     * @return string
     */
    public function getClientPublicId();

    /**
     * @param string $client_public_id
     */
    public function setClientPublicId($client_public_id);
    
    /**
     * @return string[]
     */
    public function getRequestedScopes();

    /**
     * @param string[] $requested_scopes
     */
    public function setRequestedScopes(array $requested_scopes);

    /**
     * @return string[]
     */
    public function getValidatedScopes();

    /**
     * @param string[] $validated_scopes
     */
    public function setValidatedScopes(array $validated_scopes);
}