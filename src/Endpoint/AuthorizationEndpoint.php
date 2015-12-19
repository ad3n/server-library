<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2015 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Endpoint;

use OAuth2\Behaviour\HasConfiguration;
use OAuth2\Behaviour\HasExceptionManager;
use OAuth2\Behaviour\HasResponseModeSupport;
use OAuth2\Behaviour\HasScopeManager;
use OAuth2\Client\ConfidentialClientInterface;
use OAuth2\Client\RegisteredClientInterface;
use OAuth2\Configuration\ConfigurationInterface;
use OAuth2\Exception\BaseExceptionInterface;
use OAuth2\Exception\ExceptionManagerInterface;
use OAuth2\Grant\ResponseTypeSupportInterface;
use OAuth2\Scope\ScopeManagerInterface;
use OAuth2\Util\Uri;
use Psr\Http\Message\ResponseInterface;

final class AuthorizationEndpoint implements AuthorizationEndpointInterface
{
    use HasConfiguration;
    use HasExceptionManager;
    use HasScopeManager;
    use HasResponseModeSupport;

    /**
     * @var \OAuth2\Grant\ResponseTypeSupportInterface[]
     */
    private $response_types = [];

    /**
     * AuthorizationEndpoint constructor.
     *
     * @param \OAuth2\Scope\ScopeManagerInterface          $scope_manager
     * @param \OAuth2\Exception\ExceptionManagerInterface  $exception_manager
     * @param \OAuth2\Configuration\ConfigurationInterface $configuration
     */
    public function __construct(
        ScopeManagerInterface $scope_manager,
        ExceptionManagerInterface $exception_manager,
        ConfigurationInterface $configuration
    ) {
        $this->setExceptionManager($exception_manager);
        $this->setConfiguration($configuration);
        $this->setScopeManager($scope_manager);
    }

    /**
     * @param \OAuth2\Grant\ResponseTypeSupportInterface $response_type
     */
    public function addResponseType(ResponseTypeSupportInterface $response_type)
    {
        $type = $response_type->getResponseType();
        if (!array_key_exists($type, $this->response_types)) {
            $this->response_types[$type] = $response_type;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authorize(Authorization $authorization, ResponseInterface &$response)
    {
        $redirect_uri = $this->checkRedirectUri($authorization);
        $this->checkRedirectUriFragment($redirect_uri);
        $this->checkSecuredRedirectUri($redirect_uri);

        $this->checkState($authorization);
        $this->checkScope($authorization);

        $types = $this->getResponseTypes($authorization);

        $response_mode = $this->getResponseMode($types, $authorization);

        if ($authorization->isAuthorized() === false) {
            $params = [
                'transport_mode' => $response_mode->getName(),
                'redirect_uri'   => $authorization->get('redirect_uri'),
            ];
            if (true === $authorization->has('state')) {
                $params['state'] = $authorization->get('state');
            }
            $exception = $this->getExceptionManager()->getException(ExceptionManagerInterface::REDIRECT, ExceptionManagerInterface::ACCESS_DENIED, 'The resource owner denied access to your client', $params);
            $exception->getHttpResponse($response);

            return;
        }

        $result = [];
        foreach ($types as $type) {
            $temp = $type->grantAuthorization($authorization);
            $result = array_merge($result, $temp);
            if ($authorization->has('state')) {
                $result['state'] = $authorization->get('state');
            }
        }

        $response_mode->prepareResponse($redirect_uri, $result, $response);
    }

    /**
     * @param \OAuth2\Endpoint\Authorization $authorization An array with mixed values
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     *
     * @return string
     */
    private function checkRedirectUri(Authorization $authorization)
    {
        $this->checkRedirectUriIfRequired($authorization);

        $redirect_uri = $authorization->has('redirect_uri') ? $authorization->get('redirect_uri') : null;
        $redirect_uris = $this->getClientRedirectUris($authorization);

        if (empty($redirect_uri) && empty($redirect_uris)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'The "redirect_uri" parameter is missing. Add "redirect_uri" parameter or store redirect URIs to your client');
        }
        if (!empty($redirect_uri) && !empty($redirect_uris) && false === Uri::isRedirectUriAllowed($redirect_uri, $redirect_uris)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'The specified redirect URI is not valid');
        }
        if (!empty($redirect_uri)) {
            return $redirect_uri;
        }

        return $redirect_uris[0];
    }

    /**
     * @param \OAuth2\Endpoint\Authorization $authorization
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkRedirectUriIfRequired(Authorization $authorization)
    {
        //If the redirect URI is not set and the configuration requires it, throws an exception
        if (true === $this->getConfiguration()->get('enforce_redirect_uri', false) && false === $authorization->has('redirect_uri')) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'The "redirect_uri" parameter is mandatory');
        }
    }

    /**
     * Check if a fragment is in the URI.
     *
     * @param string $redirect_uri An array with mixed values
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkRedirectUriFragment($redirect_uri)
    {
        $uri = parse_url($redirect_uri);
        if (isset($uri['fragment'])) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'The "redirect_uri" must not contain fragment');
        }
    }

    /**
     * Check if the redirect URI is secured if the configuration requires it.
     *
     * @param string $redirect_uri The redirect uri to check
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2.1
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkSecuredRedirectUri($redirect_uri)
    {
        if (true === $this->getConfiguration()->get('enforce_secured_redirect_uri', false) && 'https' !== substr($redirect_uri, 0, 5)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'The "redirect_uri" must be secured');
        }
    }

    /**
     * Check if the redirect URIs stored by the client.
     *
     * @param \OAuth2\Endpoint\Authorization $authorization An array with mixed values
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     *
     * @return array
     */
    private function getClientRedirectUris(Authorization $authorization)
    {
        $client = $authorization->getClient();
        if (!$client instanceof RegisteredClientInterface) {
            return [];
        }

        $redirect_uris = $client->getRedirectUris();
        /*
         * @see http://tools.ietf.org/html/rfc6749#section-3.1.2.2
         */
        if (!empty($redirect_uris)) {
            return $redirect_uris;
        }

        $this->checkRedirectUriIfRequiredForRegisteredClients();
        $this->checkRedirectUriForNonConfidentialClient($client);
        $this->checkRedirectUriForConfidentialClient($client, $authorization);

        return [];
    }

    /**
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkRedirectUriIfRequiredForRegisteredClients()
    {
        if (true === $this->getConfiguration()->get('enforce_registered_client_redirect_uris', false)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_CLIENT, 'Registered clients must register at least one redirect URI');
        }
    }

    /**
     * @param \OAuth2\Client\RegisteredClientInterface $client
     * 
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkRedirectUriForNonConfidentialClient(RegisteredClientInterface $client)
    {
        if (!$client instanceof ConfidentialClientInterface) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_CLIENT, 'Non-confidential clients must register at least one redirect URI');
        }
    }

    /**
     * @param \OAuth2\Client\RegisteredClientInterface $client
     * @param \OAuth2\Endpoint\Authorization           $authorization
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkRedirectUriForConfidentialClient(RegisteredClientInterface $client, Authorization $authorization)
    {
        if ($client instanceof ConfidentialClientInterface && $authorization->has('response_type') && $authorization->get('response_type') === 'token') {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_CLIENT, 'Confidential clients must register at least one redirect URI when using "token" response type');
        }
    }

    /**
     * @param \OAuth2\Endpoint\Authorization $authorization An array with mixed values
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     */
    private function checkState(Authorization $authorization)
    {
        if (!$authorization->has('state') && $this->getConfiguration()->get('enforce_state', false)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'The "state" parameter is mandatory');
        }
    }

    private function checkScope(Authorization &$authorization)
    {
        try {
            $scope = $this->getScopeManager()->checkScopePolicy($authorization->getClient(), $authorization->getScopes());
            $authorization->setScopes($scope);
        } catch (BaseExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, $e->getMessage());
        }

        $availableScopes = $this->getScopeManager()->getAvailableScopes($authorization->getClient());
        if (!$this->getScopeManager()->checkScopes($scope, $availableScopes)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_SCOPE, 'An unsupported scope was requested. Available scopes for the client are ['.implode(',', $availableScopes).']');
        }
    }

    /**
     * @param \OAuth2\Endpoint\Authorization $authorization
     *
     * @throws \OAuth2\Exception\BaseExceptionInterface
     *
     * @return \OAuth2\Grant\ResponseTypeSupportInterface[]
     */
    private function getResponseTypes(Authorization $authorization)
    {
        /*
         * @see http://tools.ietf.org/html/rfc6749#section-3.1.1
         */
        if (!$authorization->has('response_type')) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'Invalid "response_type" parameter or parameter is missing');
        }

        $types = explode(' ', $authorization->get('response_type'));
        $response_types = [];

        /*
         * Multiple response types support must be enabled.
         * This option should be set to true only if OpenID Connect is used.
         */
        if (1 < count($types) && false === $this->getConfiguration()->get('multiple_response_types_support_enabled', false)) {
            throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'Multiple response types is disabled.');
        }

        foreach ($types as $type) {
            if (1 < count(array_keys($types, $type))) {
                throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'A response type appears more than once.');
            }
            if (array_key_exists($type, $this->response_types)) {
                $response_types[] = $this->response_types[$type];
            } else {
                throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::INVALID_REQUEST, 'Response type "'.$type.'" is not supported by this server');
            }

            if (!$authorization->getClient()->isAllowedGrantType($type)) {
                throw $this->getExceptionManager()->getException(ExceptionManagerInterface::BAD_REQUEST, ExceptionManagerInterface::UNAUTHORIZED_CLIENT, 'The response type "'.$authorization->get('response_type').'" is unauthorized for this client.');
            }
        }

        return $response_types;
    }
}
