<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Endpoint\ClientRegistration\Rule;

use Assert\Assertion;
use OAuth2\OpenIdConnect\UserInfo\HasUserinfo;
use OAuth2\OpenIdConnect\UserInfo\UserInfoInterface;

final class SubjectTypeRule implements ClientRegistrationRuleInterface
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
    public function checkRegistrationParameters(array $registration_parameters, array &$metadatas)
    {
        if (!array_key_exists('subject_type', $registration_parameters)) {
            return;
        }

        $supported_type = ['public'];
        if ($this->getUserinfo()->isPairwiseSubjectIdentifierSupported()) {
            $supported_type[] = 'pairwise';
        }

        Assertion::inArray($registration_parameters['subject_type'], $supported_type, sprintf('The subject type "%s" is not supported. Please use one of the following value: %s', $registration_parameters['subject_type'], $supported_type));

        $metadatas['subject_type'] = $registration_parameters['subject_type'];
    }
}