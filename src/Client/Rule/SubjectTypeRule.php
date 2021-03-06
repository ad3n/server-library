<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Client\Rule;

use Assert\Assertion;
use OAuth2\Client\ClientInterface;
use OAuth2\OpenIdConnect\UserInfo\HasUserinfo;
use OAuth2\OpenIdConnect\UserInfo\UserInfoInterface;

class SubjectTypeRule implements RuleInterface
{
    use HasUserinfo;

    /**
     * SubjectTypeRule constructor.
     *
     * @param \OAuth2\OpenIdConnect\UserInfo\UserInfoInterface $userinfo
     */
    public function __construct(UserInfoInterface $userinfo)
    {
        $this->setUserinfo($userinfo);
    }

    /**
     * {@inheritdoc}
     */
    public function check(ClientInterface $client, array $registration_parameters)
    {
        if (!array_key_exists('subject_type', $registration_parameters)) {
            return;
        }

        Assertion::string($registration_parameters['subject_type'], 'Invalid parameter "subject_type". The value must be a string.');
        $supported_types = ['public'];
        if ($this->getUserinfo()->isPairwiseSubjectIdentifierSupported()) {
            $supported_types[] = 'pairwise';
        }

        Assertion::inArray($registration_parameters['subject_type'], $supported_types, sprintf('The subject type "%s" is not supported. Please use one of the following value: %s', $registration_parameters['subject_type'], json_encode($supported_types)));

        $client->set('subject_type', $registration_parameters['subject_type']);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreserverParameters()
    {
        return [];
    }
}
