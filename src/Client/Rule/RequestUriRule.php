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

class RequestUriRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function check(ClientInterface $client, array $registration_parameters)
    {
        if (!array_key_exists('request_uris', $registration_parameters)) {
            return;
        }
        Assertion::isArray($registration_parameters['request_uris'], 'The parameter "request_uris" must be a list of URI.');
        Assertion::allUrl($registration_parameters['request_uris'], 'The parameter "request_uris" must be a list of URI.');
        $client->set('request_uris', $registration_parameters['request_uris']);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreserverParameters()
    {
        return [];
    }
}
