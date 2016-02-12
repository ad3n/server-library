<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Grant;

use OAuth2\Behaviour\HasAccessTokenManager;
use OAuth2\Behaviour\HasTokenTypeManager;
use OAuth2\Endpoint\Authorization;
use OAuth2\Token\AccessTokenManagerInterface;
use OAuth2\Token\TokenTypeManagerInterface;

/**
 * This response type has been introduced by OpenID Connect
 * It creates an access token, but does not returns anything.
 *
 * At this time, this response type is not complete, because it always redirect the client.
 * But if no redirect URI is specified, no redirection should occurred as per OpenID Connect specification.
 *
 * @see http://openid.net/specs/oauth-v2-multiple-response-types-1_0.html#none
 */
final class NoneResponseType implements ResponseTypeSupportInterface
{
    use HasTokenTypeManager;
    use HasAccessTokenManager;

    /**
     * @var \OAuth2\Grant\NoneResponseTypeListenerInterface[]
     */
    private $listeners = [];

    /**
     * @var bool
     */
    private $access_token_type_parameter_allowed = false;

    /**
     * NoneResponseType constructor.
     *
     * @param \OAuth2\Token\TokenTypeManagerInterface   $token_type_manager
     * @param \OAuth2\Token\AccessTokenManagerInterface $access_token_manager
     */
    public function __construct(TokenTypeManagerInterface $token_type_manager,
                                AccessTokenManagerInterface $access_token_manager
    ) {
        $this->setTokenTypeManager($token_type_manager);
        $this->setAccessTokenManager($access_token_manager);
    }

    /**
     * @param \OAuth2\Grant\NoneResponseTypeListenerInterface $listener
     */
    public function addListener(NoneResponseTypeListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseType()
    {
        return 'none';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode()
    {
        return self::RESPONSE_TYPE_MODE_QUERY;
    }

    /**
     * {@inheritdoc}
     */
    public function grantAuthorization(Authorization $authorization)
    {
        if (true === $this->isAccessTokenTypeParameterAllowed() && array_key_exists('token_type', $authorization->getQueryParams())) {
            $token_type = $this->getTokenTypeManager()->getTokenType($authorization->getQueryParams()['token_type']);
        } else {
            $token_type = $this->getTokenTypeManager()->getDefaultTokenType();
        }

        $token = $this->getAccessTokenManager()->createAccessToken(
            $authorization->getClient(),
            $authorization->getEndUser(),
            $token_type->getTokenTypeInformation(),
            $authorization->getScopes()
        );

        foreach ($this->listeners as $listener) {
            $listener->call($token);
        }

        return [];
    }

    /**
     * @return bool
     */
    public function isAccessTokenTypeParameterAllowed()
    {
        return $this->access_token_type_parameter_allowed;
    }

    /**
     *
     */
    public function allowAccessTokenTypeParameter()
    {
        $this->access_token_type_parameter_allowed = true;
    }

    /**
     *
     */
    public function disallowAccessTokenTypeParameter()
    {
        $this->access_token_type_parameter_allowed = true;
    }
}
