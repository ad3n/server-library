<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test;

use Jose\Factory\DecrypterFactory;
use Jose\Factory\VerifierFactory;
use Jose\Object\JWK;
use Jose\Object\JWKInterface;
use Jose\Object\JWKSet;
use OAuth2\Client\ClientManagerSupervisor;
use OAuth2\Endpoint\AuthorizationEndpoint;
use OAuth2\Endpoint\AuthorizationFactory;
use OAuth2\Endpoint\FormPostResponseMode;
use OAuth2\Endpoint\FragmentResponseMode;
use OAuth2\Endpoint\QueryResponseMode;
use OAuth2\Endpoint\TokenEndpoint;
use OAuth2\Endpoint\TokenIntrospectionEndpoint;
use OAuth2\Endpoint\TokenRevocationEndpoint;
use OAuth2\Endpoint\TokenType\AccessToken;
use OAuth2\Endpoint\TokenType\RefreshToken;
use OAuth2\Endpoint\UserInfoEndpoint;
use OAuth2\Grant\AuthorizationCodeGrantType;
use OAuth2\Grant\ClientCredentialsGrantType;
use OAuth2\Grant\ImplicitGrantType;
use OAuth2\Grant\JWTBearerGrantType;
use OAuth2\Grant\NoneResponseType;
use OAuth2\Grant\RefreshTokenGrantType;
use OAuth2\Grant\ResourceOwnerPasswordCredentialsGrantType;
use OAuth2\Test\Stub\AuthCodeManager;
use OAuth2\Test\Stub\ClaimCheckerManager;
use OAuth2\Test\Stub\EndUserManager;
use OAuth2\Test\Stub\ExceptionManager;
use OAuth2\Test\Stub\FooBarAccessTokenUpdater;
use OAuth2\Test\Stub\JWTAccessTokenManager;
use OAuth2\Test\Stub\JWTClientManager;
use OAuth2\Test\Stub\NoneListener;
use OAuth2\Test\Stub\PasswordClientManager;
use OAuth2\Test\Stub\PublicClientManager;
use OAuth2\Test\Stub\RefreshTokenManager;
use OAuth2\Test\Stub\ResourceServerManager;
use OAuth2\Test\Stub\ScopeManager;
use OAuth2\Test\Stub\UnregisteredClientManager;
use OAuth2\Token\BearerToken;
use OAuth2\Token\IdTokenManager;
use OAuth2\Token\MacToken;
use OAuth2\Token\TokenTypeManager;
use OAuth2\Util\JWTCreator;
use OAuth2\Util\JWTLoader;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

class Base extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $realm = 'testrealm@host.com';

    /**
     * @var string
     */
    private $audience = 'My Authorization Server';

    protected function setUp()
    {
        //To fix HHVM tests on Travis-CI
        date_default_timezone_set('UTC');
    }

    /**
     * @param string      $uri
     * @param string      $method
     * @param array       $parameters
     * @param array       $server
     * @param array       $headers
     * @param null|string $content
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function createRequest($uri = '/', $method = 'GET', array $parameters = [], array $server = [], array $headers = [], $content = null)
    {
        $request = Request::create($uri, $method, $parameters, [], [], $server, $content);
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $factory = new DiactorosFactory();

        return $factory->createRequest($request);
    }

    /**
     * @var \OAuth2\Endpoint\AuthorizationFactory
     */
    private $authorization_factory;

    /**
     * @return \OAuth2\Endpoint\AuthorizationFactory
     */
    protected function getAuthorizationFactory()
    {
        if (null === $this->authorization_factory) {
            $jwt_loader = $this->getJWTLoader(
                ['HS512'],
                ['A256KW', 'A256CBC-HS512'],
                ['keys' => [
                    [
                        'kid' => 'JWK1',
                        'use' => 'enc',
                        'kty' => 'oct',
                        'k'   => 'ABEiM0RVZneImaq7zN3u_wABAgMEBQYHCAkKCwwNDg8',
                    ],
                    [
                        'kid' => 'JWK2',
                        'use' => 'sig',
                        'kty' => 'oct',
                        'k'   => 'AyM1SysPpbyDfgZld3umj1qzKObwVMkoqQ-EstJQLr_T-1qS0gZH75aKtMN3Yj0iPS4hcgUuTwjAzZr1Z9CAow',
                    ],
                ]],
                false
            );

            $this->authorization_factory = new AuthorizationFactory(
                $this->getScopeManager(),
                $this->getClientManagerSupervisor(),
                $this->getExceptionManager(),
                $jwt_loader
            );

            $this->authorization_factory->setRequestParameterSupported(true);
            $this->authorization_factory->setRequestUriParameterSupported(true);
        }

        return $this->authorization_factory;
    }

    /**
     * @var null|\OAuth2\Endpoint\TokenRevocationEndpointInterface
     */
    private $revocation_endpoint = null;

    /**
     * @return \OAuth2\Endpoint\TokenRevocationEndpointInterface
     */
    protected function getRevocationTokenEndpoint()
    {
        if (null === $this->revocation_endpoint) {
            $this->revocation_endpoint = new TokenRevocationEndpoint(
                $this->getClientManagerSupervisor(),
                $this->getExceptionManager()
            );

            $this->revocation_endpoint->addRevocationTokenType($this->getAccessTokenType());
            $this->revocation_endpoint->addRevocationTokenType($this->getRefreshTokenType());
        }

        return $this->revocation_endpoint;
    }

    /**
     * @var null|\OAuth2\Endpoint\UserInfoEndpointInterface
     */
    private $user_info_endpoint = null;

    /**
     * @return \OAuth2\Endpoint\UserInfoEndpointInterface
     */
    protected function getUserInfoEndpoint()
    {
        if (null === $this->user_info_endpoint) {
            $this->user_info_endpoint = new UserInfoEndpoint(
                $this->getTokenTypeManager(),
                $this->getJWTAccessTokenManager(),
                $this->getEndUserManager(),
                $this->getExceptionManager()
            );
        }

        return $this->user_info_endpoint;
    }

    /**
     * @var null|\OAuth2\Endpoint\TokenIntrospectionEndpoint
     */
    private $token_introspection_endpoint = null;

    /**
     * @return \OAuth2\Endpoint\TokenIntrospectionEndpoint
     */
    protected function getTokenIntrospectionEndpoint()
    {
        if (null === $this->token_introspection_endpoint) {
            $this->token_introspection_endpoint = new TokenIntrospectionEndpoint(
                $this->getClientManagerSupervisor(),
                $this->getExceptionManager()
            );

            $this->token_introspection_endpoint->addIntrospectionTokenType($this->getAccessTokenType());
            $this->token_introspection_endpoint->addIntrospectionTokenType($this->getRefreshTokenType());
        }

        return $this->token_introspection_endpoint;
    }

    /**
     * @var null|\OAuth2\Endpoint\TokenEndpoint
     */
    private $token_endpoint = null;

    /**
     * @return \OAuth2\Endpoint\TokenEndpoint
     */
    protected function getTokenEndpoint()
    {
        if (null === $this->token_endpoint) {
            $this->token_endpoint = new TokenEndpoint(
                $this->getTokenTypeManager(),
                $this->getJWTAccessTokenManager(),
                $this->getClientManagerSupervisor(),
                $this->getEndUserManager(),
                $this->getScopeManager(),
                $this->getExceptionManager(),
                $this->getRefreshTokenManager(),
                $this->getIdTokenManager()
            );

            $this->token_endpoint->addGrantType($this->getAuthorizationCodeGrantType());
            $this->token_endpoint->addGrantType($this->getAuthorizationCodeGrantType());
            $this->token_endpoint->addGrantType($this->getClientCredentialsGrantType());
            $this->token_endpoint->addGrantType($this->getRefreshTokenGrantType());
            $this->token_endpoint->addGrantType($this->getResourceOwnerPasswordCredentialsGrantType());
            $this->token_endpoint->addGrantType($this->getJWTBearerGrantType());

            $this->token_endpoint->setAccessTokenTypeParameterAllowed(true);


        }

        return $this->token_endpoint;
    }

    /**
     * @var null|\OAuth2\Endpoint\AuthorizationEndpoint
     */
    private $authorization_endpoint = null;

    /**
     * @return \OAuth2\Endpoint\AuthorizationEndpoint
     */
    protected function getAuthorizationEndpoint()
    {
        if (null === $this->authorization_endpoint) {
            $this->authorization_endpoint = new AuthorizationEndpoint(
                $this->getScopeManager(),
                $this->getExceptionManager()
            );

            $this->authorization_endpoint->addResponseType($this->getAuthorizationCodeGrantType());
            $this->authorization_endpoint->addResponseType($this->getImplicitGrantType());
            $this->authorization_endpoint->addResponseType($this->getNoneResponseType());

            $this->authorization_endpoint->addResponseMode(new QueryResponseMode());
            $this->authorization_endpoint->addResponseMode(new FragmentResponseMode());
            $this->authorization_endpoint->addResponseMode(new FormPostResponseMode());

            $this->authorization_endpoint->setResponseModeParameterInAuthorizationRequestAllowed(true);
            $this->authorization_endpoint->setStateParameterEnforced(true);
        }

        return $this->authorization_endpoint;
    }

    /**
     * @var null|\OAuth2\Test\Stub\ExceptionManager
     */
    private $exception_manager = null;

    /**
     * @return \OAuth2\Test\Stub\ExceptionManager
     */
    protected function getExceptionManager()
    {
        if (null === $this->exception_manager) {
            $this->exception_manager = new ExceptionManager();
        }

        return $this->exception_manager;
    }

    /**
     * @var null|\OAuth2\Test\Stub\EndUserManager
     */
    private $end_user_manager = null;

    /**
     * @return \OAuth2\Test\Stub\EndUserManager
     */
    protected function getEndUserManager()
    {
        if (null === $this->end_user_manager) {
            $this->end_user_manager = new EndUserManager();
        }

        return $this->end_user_manager;
    }

    /**
     * @var null|\OAuth2\Client\ClientManagerSupervisor
     */
    private $client_manager_supervisor = null;

    /**
     * @return \OAuth2\Client\ClientManagerSupervisor
     */
    protected function getClientManagerSupervisor()
    {
        if (null === $this->client_manager_supervisor) {
            $this->client_manager_supervisor = new ClientManagerSupervisor(
                $this->getExceptionManager()
            );

            $this->client_manager_supervisor->addClientManager($this->getUnregisteredClientManager());
            $this->client_manager_supervisor->addClientManager($this->getPasswordClientManager());
            $this->client_manager_supervisor->addClientManager($this->getPublicClientManager());
            $this->client_manager_supervisor->addClientManager($this->getJWTClientManager());
            $this->client_manager_supervisor->addClientManager($this->getResourceServerManager());
        }

        return $this->client_manager_supervisor;
    }

    /**
     * @var null|\OAuth2\Test\Stub\UnregisteredClientManager
     */
    private $unregistered_client_manager = null;

    /**
     * @return \OAuth2\Test\Stub\UnregisteredClientManager
     */
    protected function getUnregisteredClientManager()
    {
        if (null === $this->unregistered_client_manager) {
            $this->unregistered_client_manager = new UnregisteredClientManager(
                $this->getExceptionManager()
            );
        }

        return $this->unregistered_client_manager;
    }

    /**
     * @var null|\OAuth2\Test\Stub\PublicClientManager
     */
    private $public_client_manager = null;

    /**
     * @return \OAuth2\Test\Stub\PublicClientManager
     */
    protected function getPublicClientManager()
    {
        if (null === $this->public_client_manager) {
            $this->public_client_manager = new PublicClientManager(
                $this->getExceptionManager()
            );
        }

        return $this->public_client_manager;
    }

    /**
     * @var null|\OAuth2\Test\Stub\PasswordClientManager
     */
    private $password_client_manager = null;

    /**
     * @return \OAuth2\Test\Stub\PasswordClientManager
     */
    protected function getPasswordClientManager()
    {
        if (null === $this->password_client_manager) {
            $this->password_client_manager = new PasswordClientManager(
                $this->getExceptionManager(),
                $this->realm
            );

            $this->password_client_manager->createClients();
            $this->password_client_manager->setPasswordClientCredentialsInBodyRequestAllowed(true);
        }

        return $this->password_client_manager;
    }

    /**
     * @var null|\OAuth2\Test\Stub\ResourceServerManager
     */
    private $resource_server_manager = null;

    /**
     * @return \OAuth2\Test\Stub\ResourceServerManager
     */
    protected function getResourceServerManager()
    {
        if (null === $this->resource_server_manager) {
            $this->resource_server_manager = new ResourceServerManager();

            $this->resource_server_manager->createResourceServers();
        }

        return $this->resource_server_manager;
    }

    /**
     * @var null|\OAuth2\Test\Stub\JWTClientManager
     */
    private $jwt_client_manager = null;

    /**
     * @return \OAuth2\Test\Stub\JWTClientManager
     */
    protected function getJWTClientManager()
    {
        if (null === $this->jwt_client_manager) {
            $jwt_loader = $this->getJWTLoader(
                ['HS512'],
                ['A256KW', 'A256CBC-HS512'],
                ['keys' => [
                    [
                        'kid' => 'JWK1',
                        'use' => 'enc',
                        'kty' => 'oct',
                        'k'   => 'ABEiM0RVZneImaq7zN3u_wABAgMEBQYHCAkKCwwNDg8',
                    ],
                    [
                        'kid' => 'JWK2',
                        'use' => 'sig',
                        'kty' => 'oct',
                        'k'   => 'AyM1SysPpbyDfgZld3umj1qzKObwVMkoqQ-EstJQLr_T-1qS0gZH75aKtMN3Yj0iPS4hcgUuTwjAzZr1Z9CAow',
                    ],
                ]],
                false
            );

            $this->jwt_client_manager = new JWTClientManager(
                $jwt_loader,
                $this->getExceptionManager()
            );
        }

        return $this->jwt_client_manager;
    }

    /**
     * @var null|\OAuth2\Grant\AuthorizationCodeGrantType
     */
    private $authorization_code_grant_type = null;

    /**
     * @return \OAuth2\Grant\AuthorizationCodeGrantType
     */
    protected function getAuthorizationCodeGrantType()
    {
        if (null === $this->authorization_code_grant_type) {
            $this->authorization_code_grant_type = new AuthorizationCodeGrantType(
                $this->getAuthCodeManager(),
                $this->getExceptionManager()
            );

            $this->authorization_code_grant_type->setPKCEForPublicClientsEnforced(true);
        }

        return $this->authorization_code_grant_type;
    }

    /**
     * @var null|\OAuth2\Grant\ClientCredentialsGrantType
     */
    private $client_credentials_grant_type = null;

    /**
     * @return \OAuth2\Grant\ClientCredentialsGrantType
     */
    protected function getClientCredentialsGrantType()
    {
        if (null === $this->client_credentials_grant_type) {
            $this->client_credentials_grant_type = new ClientCredentialsGrantType(
                $this->getExceptionManager()
            );
            $this->client_credentials_grant_type->setRefreshTokenIssuedWithAccessToken(false);
        }

        return $this->client_credentials_grant_type;
    }

    /**
     * @var null|\OAuth2\Grant\JWTBearerGrantType
     */
    private $jwt_bearer_grant_type = null;

    /**
     * @return \OAuth2\Grant\JWTBearerGrantType
     */
    protected function getJWTBearerGrantType()
    {
        if (null === $this->jwt_bearer_grant_type) {
            $jwt_loader = $this->getJWTLoader(
                ['HS512'],
                ['A256KW', 'A256CBC-HS512'],
                ['keys' => [
                    [
                        'kid' => 'JWK1',
                        'use' => 'enc',
                        'kty' => 'oct',
                        'k'   => 'ABEiM0RVZneImaq7zN3u_wABAgMEBQYHCAkKCwwNDg8',
                    ],
                    [
                        'kid' => 'JWK2',
                        'use' => 'sig',
                        'kty' => 'oct',
                        'k'   => 'AyM1SysPpbyDfgZld3umj1qzKObwVMkoqQ-EstJQLr_T-1qS0gZH75aKtMN3Yj0iPS4hcgUuTwjAzZr1Z9CAow',
                    ],
                ]],
                true
            );

            $this->jwt_bearer_grant_type = new JWTBearerGrantType(
                $jwt_loader,
                $this->getExceptionManager()
            );
            $this->jwt_bearer_grant_type->setRefreshTokenIssuedWithAccessToken(false);
        }

        return $this->jwt_bearer_grant_type;
    }

    /**
     * @var null|\OAuth2\Test\Stub\NoneListener
     */
    private $none_listener = null;

    /**
     * @return \OAuth2\Test\Stub\NoneListener
     */
    public function getNoneListener()
    {
        if (null === $this->none_listener) {
            $this->none_listener =  new NoneListener();
        }

        return $this->none_listener;
    }

    /**
     * @var null|\OAuth2\Grant\NoneResponseType
     */
    private $none_response_type = null;

    /**
     * @return \OAuth2\Grant\NoneResponseType
     */
    protected function getNoneResponseType()
    {
        if (null === $this->none_response_type) {
            $this->none_response_type = new NoneResponseType(
                $this->getTokenTypeManager(),
                $this->getJWTAccessTokenManager()
            );

            $this->none_response_type->addListener($this->getNoneListener());
            $this->none_response_type->setAccessTokenTypeParameterAllowed(true);
        }

        return $this->none_response_type;
    }

    /**
     * @var null|\OAuth2\Grant\ImplicitGrantType
     */
    private $implicit_grant_type = null;

    /**
     * @return \OAuth2\Grant\ImplicitGrantType
     */
    protected function getImplicitGrantType()
    {
        if (null === $this->implicit_grant_type) {
            $this->implicit_grant_type = new ImplicitGrantType(
                $this->getTokenTypeManager(),
                $this->getJWTAccessTokenManager()
            );
            $this->implicit_grant_type->setAccessTokenTypeParameterAllowed(false);
        }

        return $this->implicit_grant_type;
    }

    /**
     * @var null|\OAuth2\Grant\RefreshTokenGrantType
     */
    private $refresh_token_grant_type = null;

    /**
     * @return \OAuth2\Grant\RefreshTokenGrantType
     */
    protected function getRefreshTokenGrantType()
    {
        if (null === $this->refresh_token_grant_type) {
            $this->refresh_token_grant_type = new RefreshTokenGrantType(
                $this->getRefreshTokenManager(),
                $this->getExceptionManager()
            );
        }

        return $this->refresh_token_grant_type;
    }

    /**
     * @return null|\OAuth2\Grant\ResourceOwnerPasswordCredentialsGrantType
     */
    private $resource_owner_password_credentials_grant_type = null;

    /**
     * @return \OAuth2\Grant\ResourceOwnerPasswordCredentialsGrantType
     */
    protected function getResourceOwnerPasswordCredentialsGrantType()
    {
        if (null === $this->resource_owner_password_credentials_grant_type) {
            $this->resource_owner_password_credentials_grant_type = new ResourceOwnerPasswordCredentialsGrantType(
                $this->getEndUserManager(),
                $this->getExceptionManager()
            );
        }

        return $this->resource_owner_password_credentials_grant_type;
    }

    /**
     * @return null|\OAuth2\Scope\ScopeManager
     */
    private $scope_manager = null;

    /**
     * @return \OAuth2\Scope\ScopeManager
     */
    protected function getScopeManager()
    {
        if (null === $this->scope_manager) {
            $this->scope_manager = new ScopeManager(
                $this->getExceptionManager()
            );
        }

        return $this->scope_manager;
    }

    /**
     * @return null|\OAuth2\Token\JWTAccessTokenManager
     */
    private $jwt_access_token_manager = null;

    /**
     * @return \OAuth2\Token\JWTAccessTokenManager
     */
    protected function getJWTAccessTokenManager()
    {
        if (null === $this->jwt_access_token_manager) {

            $jwt_creator = $this->getJWTCreator(
                'HS512',
                new JWK([
                    'kid' => 'JWK2',
                    'use' => 'sig',
                    'kty' => 'oct',
                    'k'   => 'AyM1SysPpbyDfgZld3umj1qzKObwVMkoqQ-EstJQLr_T-1qS0gZH75aKtMN3Yj0iPS4hcgUuTwjAzZr1Z9CAow',
                ]),
                'A256KW',
                'A256CBC-HS512',
                new JWK([
                    'kid' => 'JWK1',
                    'use' => 'enc',
                    'kty' => 'oct',
                    'k'   => 'ABEiM0RVZneImaq7zN3u_wABAgMEBQYHCAkKCwwNDg8',
                ])
            );

            $jwt_loader = $this->getJWTLoader(
                ['HS512'],
                ['A256KW', 'A256CBC-HS512'],
                ['keys' => [
                    [
                        'kid' => 'JWK1',
                        'use' => 'enc',
                        'kty' => 'oct',
                        'k'   => 'ABEiM0RVZneImaq7zN3u_wABAgMEBQYHCAkKCwwNDg8',
                    ],
                    [
                        'kid' => 'JWK2',
                        'use' => 'sig',
                        'kty' => 'oct',
                        'k'   => 'AyM1SysPpbyDfgZld3umj1qzKObwVMkoqQ-EstJQLr_T-1qS0gZH75aKtMN3Yj0iPS4hcgUuTwjAzZr1Z9CAow',
                    ],
                ]],
                true
            );

            $this->jwt_access_token_manager = new JWTAccessTokenManager(
                $jwt_loader,
                $jwt_creator,
                $this->audience
            );
            $this->jwt_access_token_manager->addTokenUpdater(new FooBarAccessTokenUpdater());
        }

        return $this->jwt_access_token_manager;
    }

    /**
     * @return null|\OAuth2\Token\BearerToken
     */
    private $bearer_token_type = null;

    /**
     * @return \OAuth2\Token\BearerToken
     */
    protected function getBearerTokenType()
    {
        if (null === $this->bearer_token_type) {
            $this->bearer_token_type = new BearerToken();
        }

        return $this->bearer_token_type;
    }

    /**
     * @return null|\OAuth2\Token\MacToken
     */
    private $mac_token_type = null;

    /**
     * @return \OAuth2\Token\MacToken
     */
    protected function getMacTokenType()
    {
        if (null === $this->mac_token_type) {
            $this->mac_token_type = new MacToken();
            
            $this->mac_token_type->setMacKeyCharset('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            $this->mac_token_type->setMacKeyMinLength(10);
            $this->mac_token_type->setMacKeyMaxLength(20);
            $this->mac_token_type->setMacAlgorithm('hmac-sha-256');
        }

        return $this->mac_token_type;
    }

    /**
     * @return null|\OAuth2\Token\TokenTypeManagerInterface
     */
    private $token_type_manager = null;

    /**
     * @return \OAuth2\Token\TokenTypeManagerInterface
     */
    protected function getTokenTypeManager()
    {
        if (null === $this->token_type_manager) {
            $this->token_type_manager = new TokenTypeManager(
                $this->getExceptionManager()
            );

            $this->token_type_manager->addTokenType($this->getBearerTokenType(), true);
            $this->token_type_manager->addTokenType($this->getMacTokenType());
        }

        return $this->token_type_manager;
    }

    /**
     * @return null|\OAuth2\Test\Stub\RefreshTokenManager
     */
    private $refresh_token_manager = null;

    /**
     * @return \OAuth2\Test\Stub\RefreshTokenManager
     */
    protected function getRefreshTokenManager()
    {
        if (null === $this->refresh_token_manager) {
            $this->refresh_token_manager = new RefreshTokenManager();
            
            $this->refresh_token_manager->setRefreshTokenCharset('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            $this->refresh_token_manager->setRefreshTokenMinLength(10);
            $this->refresh_token_manager->setRefreshTokenMaxLength(20);
            $this->refresh_token_manager->setRefreshTokenLifetime(36000);
        }

        return $this->refresh_token_manager;
    }

    /**
     * @return \OAuth2\Test\Stub\AuthCodeManager
     */
    private $auth_code_manager = null;

    /**
     * @return \OAuth2\Test\Stub\AuthCodeManager
     */
    protected function getAuthCodeManager()
    {
        if (null === $this->auth_code_manager) {
            $this->auth_code_manager = new AuthCodeManager();
            
            $this->auth_code_manager->setAuthorizationCodeCharset('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
            $this->auth_code_manager->setAuthorizationCodeMinLength(10);
            $this->auth_code_manager->setAuthorizationCodeMaxLength(20);
            $this->auth_code_manager->setAuthorizationCodeLifetime(15);
        }

        return $this->auth_code_manager;
    }

    /**
     * @return null|\\OAuth2\Token\IdTokenManager
     */
    private $id_token_manager = null;

    /**
     * @return \OAuth2\Token\IdTokenManager
     */
    protected function getIdTokenManager()
    {
        if (null === $this->id_token_manager) {
            $this->id_token_manager = new IdTokenManager(
                $this->getExceptionManager(),
                'HS512',
                new JWK([
                    'kid' => 'JWK2',
                    'use' => 'sig',
                    'kty' => 'oct',
                    'k'   => 'AyM1SysPpbyDfgZld3umj1qzKObwVMkoqQ-EstJQLr_T-1qS0gZH75aKtMN3Yj0iPS4hcgUuTwjAzZr1Z9CAow',
                ]),
                $this->audience
            );
        }

        return $this->id_token_manager;
    }

    /**
     * @param string[] $allowed_signature_algorithms
     * @param string[] $allowed_encryption_algorithms
     * @param array    $key_set
     * @param bool     $is_encryption_required
     *
     * @return \OAuth2\Util\JWTLoader
     */
    protected function getJWTLoader(array $allowed_signature_algorithms, array $allowed_encryption_algorithms, array $key_set, $is_encryption_required = false)
    {
        $jwt_loader = new JWTLoader(
            $this->getClaimCheckerManager(),
            $this->getVerifier($allowed_signature_algorithms),
            $this->getDecrypter($allowed_encryption_algorithms),
            $this->getExceptionManager(),
            new JWKSet($key_set),
            $allowed_encryption_algorithms,
            $is_encryption_required
        );

        return $jwt_loader;
    }

    /**
     * @param string                          $signature_algorithm
     * @param \Jose\Object\JWKInterface      $signature_key
     * @param null|string                    $key_encryption_algorithm
     * @param null|string                    $content_encryption_algorithm
     * @param \Jose\Object\JWKInterface|null $sender_key
     *
     * @return \OAuth2\Util\JWTCreator
     */
    protected function getJWTCreator($signature_algorithm, JWKInterface $signature_key, $key_encryption_algorithm = null, $content_encryption_algorithm = null, JWKInterface $sender_key = null)
    {
        $jwt_creator = new JWTCreator(
            $signature_algorithm,
            $signature_key,
            $key_encryption_algorithm,
            $content_encryption_algorithm,
            $sender_key
        );

        return $jwt_creator;
    }

    /**
     * @param string[] $allowed_encryption_algorithms
     *
     * @return \Jose\DecrypterInterface
     */
    protected function getDecrypter($allowed_encryption_algorithms)
    {
        return DecrypterFactory::createDecrypter(
            $allowed_encryption_algorithms,
            $this->getCompressionManager()
        );
    }

    /**
     * @param string[] $allowed_encryption_algorithms
     *
     * @return \Jose\VerifierInterface
     */
    protected function getVerifier($allowed_encryption_algorithms)
    {
        return VerifierFactory::createVerifier(
            $allowed_encryption_algorithms
        );
    }

    /**
     * @var null|\Jose\ClaimChecker\ClaimCheckerManagerInterface
     */
    private $claim_checker_manager = null;

    /**
     * @return \Jose\ClaimChecker\ClaimCheckerManagerInterface
     */
    protected function getClaimCheckerManager()
    {
        if (null === $this->claim_checker_manager) {

            $this->claim_checker_manager = new ClaimCheckerManager($this->audience);
        }

        return $this->claim_checker_manager;
    }

    /**
     * @return string[]
     */
    protected function getCompressionManager()
    {
        return [
            'DEF',
            'GZ',
            'ZLIB',
        ];
    }

    /**
     * @return \OAuth2\Endpoint\TokenType\AccessToken
     */
    protected function getAccessTokenType()
    {
        $access_token_type =  new AccessToken(
            $this->getJWTAccessTokenManager(),
            $this->getRefreshTokenManager()
        );
        
        $access_token_type->setRefreshTokensRevokedWithAccessTokens(true);
        
        return $access_token_type;
    }

    /**
     * @return \OAuth2\Endpoint\TokenType\RefreshToken
     */
    protected function getRefreshTokenType()
    {
        return new RefreshToken($this->getRefreshTokenManager());
    }

    protected function getSupportedJWTAlgorithms()
    {
        return [
            'HS256'              => '\Jose\Algorithm\Signature\HS256',
            'HS384'              => '\Jose\Algorithm\Signature\HS384',
            'HS512'              => '\Jose\Algorithm\Signature\HS512',
            'ES256'              => '\Jose\Algorithm\Signature\ES256',
            'ES384'              => '\Jose\Algorithm\Signature\ES384',
            'ES512'              => '\Jose\Algorithm\Signature\ES512',
            'none'               => '\Jose\Algorithm\Signature\None',
            'RS256'              => '\Jose\Algorithm\Signature\RS256',
            'RS384'              => '\Jose\Algorithm\Signature\RS384',
            'RS512'              => '\Jose\Algorithm\Signature\RS512',
            'PS256'              => '\Jose\Algorithm\Signature\PS256',
            'PS384'              => '\Jose\Algorithm\Signature\PS384',
            'PS512'              => '\Jose\Algorithm\Signature\PS512',
            'A128GCM'            => '\Jose\Algorithm\ContentEncryption\A128GCM',
            'A192GCM'            => '\Jose\Algorithm\ContentEncryption\A192GCM',
            'A256GCM'            => '\Jose\Algorithm\ContentEncryption\A256GCM',
            'A128CBC-HS256'      => '\Jose\Algorithm\ContentEncryption\A128CBCHS256',
            'A192CBC-HS384'      => '\Jose\Algorithm\ContentEncryption\A192CBCHS384',
            'A256CBC-HS512'      => '\Jose\Algorithm\ContentEncryption\A256CBCHS512',
            'A128KW'             => '\Jose\Algorithm\KeyEncryption\A128KW',
            'A192KW'             => '\Jose\Algorithm\KeyEncryption\A192KW',
            'A256KW'             => '\Jose\Algorithm\KeyEncryption\A256KW',
            'A128GCMKW'          => '\Jose\Algorithm\KeyEncryption\A128GCMKW',
            'A192GCMKW'          => '\Jose\Algorithm\KeyEncryption\A192GCMKW',
            'A256GCMKW'          => '\Jose\Algorithm\KeyEncryption\A256GCMKW',
            'dir'                => '\Jose\Algorithm\KeyEncryption\Dir',
            'ECDH-ES'            => '\Jose\Algorithm\KeyEncryption\ECDHES',
            'ECDH-ES+A128KW'     => '\Jose\Algorithm\KeyEncryption\ECDHESA128KW',
            'ECDH-ES+A192KW'     => '\Jose\Algorithm\KeyEncryption\ECDHESA192KW',
            'ECDH-ES+A256KW'     => '\Jose\Algorithm\KeyEncryption\ECDHESA256KW',
            'PBES2-HS256+A128KW' => '\Jose\Algorithm\KeyEncryption\PBES2HS256A128KW',
            'PBES2-HS384+A192KW' => '\Jose\Algorithm\KeyEncryption\PBES2HS384A192KW',
            'PBES2-HS512+A256KW' => '\Jose\Algorithm\KeyEncryption\PBES2HS512A256KW',
            'RSA1_5'             => '\Jose\Algorithm\KeyEncryption\RSA15',
            'RSA-OAEP'           => '\Jose\Algorithm\KeyEncryption\RSAOAEP',
            'RSA-OAEP-256'       => '\Jose\Algorithm\KeyEncryption\RSAOAEP256',
        ];
    }
}
