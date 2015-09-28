<?php

namespace OAuth2\Test\Stub;

use OAuth2\Client\PasswordClient as BasePasswordClient;
use OAuth2\Client\TokenLifetimeExtensionInterface;

class PasswordClient extends BasePasswordClient implements TokenLifetimeExtensionInterface
{
    public function getTokenLifetime($token)
    {
        switch ($token) {
            case 'authcode':
                return 10;
            case 'access_token':
                return 1000;
            case 'refresh_token':
            default:
                return 2000;
        }
    }
}
