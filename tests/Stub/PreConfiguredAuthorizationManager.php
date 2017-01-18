<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Stub;

use OAuth2\Endpoint\Authorization\PreConfiguredAuthorization\PreConfiguredAuthorization;
use OAuth2\Endpoint\Authorization\PreConfiguredAuthorization\PreConfiguredAuthorizationInterface;
use OAuth2\Endpoint\Authorization\PreConfiguredAuthorization\PreConfiguredAuthorizationManagerInterface;
use OAuth2\Model\Client\ClientId;
use OAuth2\Model\Client\ClientRepositoryInterface;

class PreConfiguredAuthorizationManager implements PreConfiguredAuthorizationManagerInterface
{
    /**
     * @var \OAuth2\Endpoint\Authorization\PreConfiguredAuthorization\PreConfiguredAuthorizationInterface[]
     */
    private $pre_configured_authorizations = [];

    /**
     * PreConfiguredAuthorizationManager constructor.
     *
     * @param ClientRepositoryInterface $clientRepository
     */
    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        foreach ($this->getPreConfiguredAuthorizations($clientRepository) as $preConfiguredAuthorization) {
            $auth = $this->createPreConfiguredAuthorization();
            $auth->setClientPublicId($preConfiguredAuthorization['client_public_id']);
            $auth->setResourceOwnerPublicId($preConfiguredAuthorization['resource_owner_public_id']);
            $auth->setUserAccountPublicId($preConfiguredAuthorization['user_account_public_id']);
            $auth->setScopes($preConfiguredAuthorization['scopes']);
            $this->savePreConfiguredAuthorization($auth);
        }
    }

    /**
     * @param ClientRepositoryInterface $clientRepository
     *
     * @return array
     */
    protected function getPreConfiguredAuthorizations(ClientRepositoryInterface $clientRepository)
    {
        return [
            [
                'client_public_id'         => $clientRepository->find(ClientId::create('foo'))->getId()->getValue(),
                'resource_owner_public_id' => 'real_user1_public_id',
                'user_account_public_id'   => 'user1',
                'scopes'                   => ['openid', 'email', 'profile'],
                'validated_scopes'         => ['openid', 'email', 'profile'],
            ],
            [
                'client_public_id'         => $clientRepository->find(ClientId::create('Mufasa'))->getId()->getValue(),
                'resource_owner_public_id' => 'real_user1_public_id',
                'user_account_public_id'   => 'user1',
                'scopes'                   => ['openid', 'email', 'profile'],
                'validated_scopes'         => ['openid', 'email', 'profile'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findOnePreConfiguredAuthorization($resource_owner_public_id, $client_public_id, array $scope)
    {
        $hash = $this->calculateHash($resource_owner_public_id, $client_public_id, $scope);
        if (array_key_exists($hash, $this->pre_configured_authorizations)) {
            return $this->pre_configured_authorizations[$hash];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createPreConfiguredAuthorization()
    {
        return new PreConfiguredAuthorization();
    }

    /**
     * {@inheritdoc}
     */
    public function savePreConfiguredAuthorization(PreConfiguredAuthorizationInterface $pre_configured_authorization)
    {
        $hash = $this->calculateHash(
            $pre_configured_authorization->getResourceOwnerPublicId(),
            $pre_configured_authorization->getClientPublicId(),
            $pre_configured_authorization->getScopes()
        );
        $this->pre_configured_authorizations[$hash] = $pre_configured_authorization;
    }

    /**
     * @param string   $resource_owner_public_id
     * @param string   $client_public_id
     * @param string[] $scope
     *
     * @return string
     */
    private function calculateHash($resource_owner_public_id, $client_public_id, array $scope)
    {
        return hash(
            'sha512',
            sprintf(
                '%s%s%s',
                $resource_owner_public_id,
                $client_public_id,
                implode(' ', $scope)
            )
        );
    }
}
