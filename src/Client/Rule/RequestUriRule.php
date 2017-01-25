<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Client\Rule;

use Assert\Assertion;
use OAuth2\Model\UserAccount\UserAccountId;

final class RequestUriRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(array $commandParameters, array $validatedParameters, UserAccountId $userAccountId, callable $next)
    {
        if (array_key_exists('request_uris', $commandParameters)) {
            Assertion::isArray($commandParameters['request_uris'], 'The parameter \'request_uris\' must be a list of URI.');
            Assertion::allUrl($commandParameters['request_uris'], 'The parameter \'request_uris\' must be a list of URI.');
            $validatedParameters['request_uris'] = $commandParameters['request_uris'];
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
