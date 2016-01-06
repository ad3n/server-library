<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Client;

/**
 * Class ResourceServer.
 */
class ResourceServer extends RegisteredClient implements ResourceServerInterface
{
    /**
     * @var string
     */
    protected $server_name;

    /**
     * @var string[]
     */
    protected $allowed_ip_addresses = [];

    /**
     * {@inheritdoc}
     */
    public function getServerName()
    {
        return $this->server_name;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedIpAddresses()
    {
        return $this->allowed_ip_addresses;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowedGrantType($grant_type)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedGrantTypes()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowedGrantTypes(array $grant_types)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function addAllowedGrantType($grant_type)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function removeAllowedGrantType($grant_type)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectUris()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setRedirectUris(array $redirect_uris)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasRedirectUri($redirect_uri)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function addRedirectUri($redirect_uri)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function removeRedirectUri($redirect_uri)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'resource_server';
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
    }
}
