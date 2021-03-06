<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\TokenEndpointAuthMethod;

use OAuth2\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;

interface TokenEndpointAuthMethodInterface
{
    /**
     * @return string[]
     */
    public function getSupportedAuthenticationMethods();

    /**
     * Find a client using the request.
     * If the client is confidential, the client credentials must be checked.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request            The request
     * @param mixed                                    $client_credentials The client credentials found in the request
     *
     * @return null|string Return the client public ID if found else null. If credentials have are needed to authenticate the client, they are set to the variable $client_credentials
     */
    public function findClient(ServerRequestInterface $request, &$client_credentials = null);

    /**
     * @param array                          $client_configuration
     * @param \OAuth2\Client\ClientInterface $client
     *
     * @throws \InvalidArgumentException
     */
    public function checkClientConfiguration(array $client_configuration, ClientInterface $client);

    /**
     * This method verifies the client credentials in the request.
     *
     * @param \OAuth2\Client\ClientInterface           $client
     * @param mixed                                    $client_credentials
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return bool Returns true if the client is authenticated, else false
     */
    public function isClientAuthenticated(ClientInterface $client, $client_credentials, ServerRequestInterface $request);

    /**
     * @return array
     */
    public function getSchemesParameters();
}
