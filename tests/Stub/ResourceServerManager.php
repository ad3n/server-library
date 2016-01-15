<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Stub;

use OAuth2\ResourceServer\ResourceServer;
use OAuth2\ResourceServer\ResourceServerInterface;
use OAuth2\ResourceServer\ResourceServerManager as Base;
use OAuth2\Util\IpAddress;
use Psr\Http\Message\ServerRequestInterface;

class ResourceServerManager extends Base
{
    /**
     * @var \OAuth2\ResourceServer\ResourceServer[]
     */
    private $resource_servers = [];

    /**
     * ResourceServerManager constructor.
     */
    public function __construct()
    {
        $this->setTrustedProxies(['127.0.0.1']);
    }

    /**
     * {@inheritdoc}
     */
    public function getClient($client_id)
    {
        return array_key_exists($client_id, $this->resource_servers) ? $this->resource_servers[$client_id] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function createResourceServers()
    {
        $server1 = new ResourceServer();
        $server1->setServerName('SERVER1');
        $server1->setAllowedIpAddresses(['127.0.0.1']);
        $server1->setPublicId('SERVER1');

        $server2 = new ResourceServer();
        $server2->setServerName('SERVER2');
        $server2->setAllowedIpAddresses(['192.168.1.12']);
        $server2->setPublicId('SERVER2');

        $this->resource_servers['SERVER1'] = $server1;
        $this->resource_servers['SERVER2'] = $server2;
    }

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
    public function findClient(ServerRequestInterface $request)
    {
        if (!$request->hasHeader('X-OAuth2-Resource-Server')) {
            return;
        }

        $server_name = $request->getHeader('X-OAuth2-Resource-Server');
        if (1 !== count($server_name)) {
            return;
        }

        $resource_server = $this->getClient($server_name[0]);
        if (!$resource_server instanceof ResourceServerInterface) {
            return;
        }

        $ip = IpAddress::getClientIp($request, $this->getTrustedProxies());

        if (!$resource_server->isIpAddressAllowed($ip)) {
            return;
        }

        return $resource_server;
    }
}