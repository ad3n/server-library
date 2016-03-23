<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Scope;

use OAuth2\Client\ClientInterface;
use OAuth2\Client\ScopeExtensionInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DefaultScopePolicy implements ScopePolicyInterface
{
    /**
     * @var string[]
     */
    private $default_scopes;

    /**
     * DefaultScopePolicy constructor.
     *
     * @param string[] $default_scopes
     */
    public function __construct(array $default_scopes)
    {
        $this->default_scopes = $default_scopes;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    public function checkScopePolicy(array &$scope, ClientInterface $client, ServerRequestInterface $request = null)
    {
        if (empty($scope)) {
            $scope = $this->getDefaultScopesForClient($client, $request);
        }
    }

    /**
     * @return \string[]
     */
    private function getDefaultScopes()
    {
        return $this->default_scopes;
    }

    /**
     * {@inheritdoc}
     */
    private function getDefaultScopesForClient(ClientInterface $client, ServerRequestInterface $request = null)
    {
        return ($client instanceof ScopeExtensionInterface && null !== $client->getDefaultScopes($request)) ? $client->getDefaultScopes($request) : $this->getDefaultScopes();
    }
}
