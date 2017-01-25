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

use OAuth2\Event\Client\ClientDeletedEvent;
use OAuth2\Model\Client\Client;
use OAuth2\Model\Client\ClientId;
use OAuth2\Model\Client\ClientRepositoryInterface;
use OAuth2\Model\UserAccount\UserAccountId;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Recorder\RecordsMessages;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @var Client[]
     */
    private $clients = [];

    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * @param RecordsMessages $eventRecorder
     */
    public function __construct(RecordsMessages $eventRecorder)
    {
        $this->eventRecorder = $eventRecorder;
        $this->clients['client1'] = Client::create(
            ClientId::create('client1'),
            [
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret'              => 'secret',
                'grant_types'                => ['client_credentials', 'password', 'refresh_token', 'authorization_code', 'urn:ietf:params:oauth:grant-type:jwt-bearer'],
            ],
            UserAccountId::create('User1')
        );
        $this->clients['client2'] = Client::create(
            ClientId::create('client2'),
            [
                'token_endpoint_auth_method' => 'none',
                'grant_types'                => ['client_credentials', 'authorization_code'],
            ],
            UserAccountId::create('User1')
        );
        $this->clients['client3'] = Client::create(
            ClientId::create('client3'),
            [
                'token_endpoint_auth_method' => 'client_secret_jwt',
                'client_secret'              => 'secret',
                'client_secret_expires_at'   => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
                'grant_types'                => ['client_credentials', 'password', 'refresh_token', 'authorization_code'],
            ],
            UserAccountId::create('User1')
        );
        $this->clients['client4'] = Client::create(
            ClientId::create('client4'),
            [
                'token_endpoint_auth_method' => 'client_secret_post',
                'client_secret'              => 'secret',
                'client_secret_expires_at'   => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
            ],
            UserAccountId::create('User1')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(UserAccountId $userAccountId, array $metadatas)
    {
        $clientId = ClientId::create(Uuid::uuid4()->toString());

        return Client::create($clientId, $metadatas, $userAccountId);
    }

    /**
     * {@inheritdoc}
     */
    public function has(ClientId $clientId): bool
    {
        return array_key_exists($clientId->getValue(), $this->clients);
    }

    /**
     * {@inheritdoc}
     */
    public function find(ClientId $clientId)
    {
        return $this->has($clientId) ? $this->clients[$clientId->getValue()] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return array_values($this->clients);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Client $client)
    {
        $this->clients[$client->getId()->getValue()] = $client;
        $events = $client->recordedMessages();
        foreach ($events as $event) {
            $this->eventRecorder->record($event);
        }
        $client->eraseMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ClientId $clientId)
    {
        if ($this->has($clientId)) {
            unset($this->clients[$clientId->getValue()]);
            $event = ClientDeletedEvent::create($clientId);
            $this->eventRecorder->record($event);
        }
    }
}
