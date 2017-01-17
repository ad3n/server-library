<?php

declare(strict_types=1);

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
use OAuth2\Model\Client\Client;
use OAuth2\Model\Client\ClientId;
use Psr\Http\Message\ServerRequestInterface;

abstract class ClientSecretPost implements TokenEndpointAuthMethodInterface
{
    /**
     * @var int
     */
    private $secretLifetime;

    public function __construct(int $secretLifetime = 0)
    {
        Assertion::greaterOrEqualThan($secretLifetime, 0);

        $this->secretLifetime = $secretLifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemesParameters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findClientId(ServerRequestInterface $request, &$clientCredentials = null)
    {
        $parameters = $request->getParsedBody() ?? [];
        if (array_key_exists('client_id', $parameters) && array_key_exists('client_secret', $parameters)) {
            $clientCredentials = $parameters['client_secret'];

            return ClientId::create($parameters['client_id']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkClientConfiguration(array $command_parameters, array &$validated_parameters)
    {
        $validated_parameters['client_secret'] = $this->createClientSecret();
        $validated_parameters['client_secret_expires_at'] = (0 === $this->secretLifetime ? 0 : time() + $this->secretLifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        return hash_equals($client->get('client_secret'), $clientCredentials);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedAuthenticationMethods(): array
    {
        return ['client_secret_post'];
    }

    /**
     * @return string
     */
    abstract protected function createClientSecret(): string;
}
