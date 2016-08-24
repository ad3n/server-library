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

use Assert\Assertion;
use OAuth2\Client\ClientInterface;
use OAuth2\Util\RequestBody;
use Psr\Http\Message\ServerRequestInterface;

class ClientSecretPost implements TokenEndpointAuthMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSchemesParameters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findClient(ServerRequestInterface $request, &$client_credentials = null)
    {
        $client_id = RequestBody::getParameter($request, 'client_id');
        $client_secret = RequestBody::getParameter($request, 'client_secret');

        if (!empty($client_id) && !empty($client_secret)) {
            $client_credentials = $client_secret;

            return $client_id;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkClientConfiguration(array $client_configuration, array &$metadatas)
    {
        Assertion::keyExists('client_secret', $client_configuration, 'The parameter "client_secret" must be set.');
        Assertion::string($client_configuration['client_secret'], 'The parameter "client_secret" must be a string.');
        $metadatas['client_secret'] = $client_configuration['client_secret'];
    }

    /**
     * {@inheritdoc}
     */
    public function isClientAuthenticated(ClientInterface $client, $client_credentials, ServerRequestInterface $request)
    {
        if (false === hash_equals($client->get('client_secret'), $client_credentials)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedAuthenticationMethods()
    {
        return ['client_secret_post'];
    }
}