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

final class ResourceServerRule implements ClientRegistrationRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function checkRegistrationParameters(array $registration_parameters, array &$metadatas)
    {
        Assertion::false(array_key_exists('is_resource_server', $registration_parameters), 'Resource server registration is forbidden.');
    }
}