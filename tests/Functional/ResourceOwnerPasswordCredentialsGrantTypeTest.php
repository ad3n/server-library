<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Functional;

use OAuth2\Exception\BaseExceptionInterface;
use OAuth2\Exception\ExceptionManagerInterface;
use OAuth2\Test\Base;
use Zend\Diactoros\Response;

/**
 * @group ResourceOwnerPasswordCredentialsGrantType
 */
class ResourceOwnerPasswordCredentialsGrantTypeTest extends Base
{
    public function testUnsecuredRequest()
    {
        $response = new Response();
        $request = $this->createRequest();

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_request', $e->getMessage());
            $this->assertEquals('The request must be secured.', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testNotPostMethod()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'GET', [], ['HTTPS' => 'on']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_request', $e->getMessage());
            $this->assertEquals('Method must be POST.', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testGrantTypeIsMissing()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', [], ['HTTPS' => 'on']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals(ExceptionManagerInterface::ERROR_INVALID_REQUEST, $e->getMessage());
            $this->assertEquals('The "grant_type" parameter is missing.', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testUnknownClient()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password'], ['HTTPS' => 'on', 'PHP_AUTH_USER' => 'plic', 'PHP_AUTH_PW' => 'secret']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_client', $e->getMessage());
            $this->assertEquals('Client authentication failed.', $e->getDescription());
            $this->assertEquals(401, $e->getHttpCode());
        }
    }

    public function testUnsupportedGrantType()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'bar'], ['HTTPS' => 'on', 'PHP_AUTH_USER' => $this->getClientManager()->getClientByName('Mufasa')->getPublicId(), 'PHP_AUTH_PW' => 'Circle Of Life']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals(ExceptionManagerInterface::ERROR_INVALID_REQUEST, $e->getMessage());
            $this->assertEquals('The grant type "bar" is not supported by this server.', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testGrantTypeUnauthorizedForClient()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password'], ['HTTPS' => 'on', 'PHP_AUTH_USER' => $this->getClientManager()->getClientByName('baz')->getPublicId(), 'PHP_AUTH_PW' => 'secret']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals(ExceptionManagerInterface::ERROR_UNAUTHORIZED_CLIENT, $e->getMessage());
            $this->assertEquals('The grant type "password" is unauthorized for this client.', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testGrantTypeAuthorizedForClient()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password', 'username' => 'user1', 'password' => 'password1'], ['HTTPS' => 'on', 'PHP_AUTH_USER' => $this->getClientManager()->getClientByName('Mufasa')->getPublicId(), 'PHP_AUTH_PW' => 'Circle Of Life']);

        $this->getTokenEndpoint()->getAccessToken($request, $response);
        $response->getBody()->rewind();

        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);
        $this->assertEquals('no-store, private', $response->getHeader('Cache-Control')[0]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('no-cache', $response->getHeader('Pragma')[0]);
        $this->assertRegExp('{"access_token":"[^"]+","expires_in":[0-9]+,"refresh_token":"[^"]+","token_type":"Bearer","foo":"bar"}', $response->getBody()->getContents());
    }

    public function testGrantTypeAuthorizedForClientButNoRefreshToken()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password', 'username' => 'user1', 'password' => 'password1'], ['HTTPS' => 'on'], ['X-OAuth2-Public-Client-ID' => $this->getClientManager()->getClientByName('foo')->getPublicId()]);

        $this->getTokenEndpoint()->getAccessToken($request, $response);
        $response->getBody()->rewind();

        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);
        $this->assertEquals('no-store, private', $response->getHeader('Cache-Control')[0]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('no-cache', $response->getHeader('Pragma')[0]);
        $this->assertRegExp('{"access_token":"[^"]+","expires_in":[0-9]+,"scope":"scope1 scope2","token_type":"Bearer","foo":"bar"}', $response->getBody()->getContents());
    }

    public function testGrantTypeAuthorizedForClientButScopeInvalid()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password', 'username' => 'user1', 'password' => 'password1', 'scope' => 'scope12'], ['HTTPS' => 'on'], ['X-OAuth2-Public-Client-ID' => $this->getClientManager()->getClientByName('foo')->getPublicId()]);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_scope', $e->getMessage());
            $this->assertEquals('An unsupported scope was requested. Available scopes are [scope1,scope2,scope3,scope4,openid,profile,email,phone,address,offline_access]', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testListSchemes()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password', 'username' => 'user1', 'password' => 'password1', 'scope' => 'scope12'], ['HTTPS' => 'on'], ['Authorization' => 'Basic']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_client', $e->getMessage());
            $this->assertEquals('Client authentication failed.', $e->getDescription());
            $this->assertEquals(401, $e->getHttpCode());
            $this->assertTrue(array_key_exists('Cache-Control', $e->getResponseHeaders()));
            $this->assertTrue(array_key_exists('Pragma', $e->getResponseHeaders()));
            $this->assertTrue(array_key_exists('WWW-Authenticate', $e->getResponseHeaders()));
            $this->assertEquals(1, count($e->getResponseHeaders()['WWW-Authenticate']));
        }
    }

    public function testWrongUsername()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password', 'username' => 'user2', 'password' => 'password1'], ['HTTPS' => 'on', 'PHP_AUTH_USER' => $this->getClientManager()->getClientByName('Mufasa')->getPublicId(), 'PHP_AUTH_PW' => 'Circle Of Life']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_grant', $e->getMessage());
            $this->assertEquals('Invalid username and password combination', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    public function testWrongPassword()
    {
        $response = new Response();
        $request = $this->createRequest('/', 'POST', ['grant_type' => 'password', 'username' => 'user1', 'password' => 'password2'], ['HTTPS' => 'on', 'PHP_AUTH_USER' => $this->getClientManager()->getClientByName('Mufasa')->getPublicId(), 'PHP_AUTH_PW' => 'Circle Of Life']);

        try {
            $this->getTokenEndpoint()->getAccessToken($request, $response);
            $this->fail('Should throw an Exception');
        } catch (BaseExceptionInterface $e) {
            $this->assertEquals('invalid_grant', $e->getMessage());
            $this->assertEquals('Invalid username and password combination', $e->getDescription());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }
}
