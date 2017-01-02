<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Endpoint\TokenRevocation;

use Psr\Http\Message\ServerRequestInterface;

final class TokenRevocationPostEndpoint extends TokenRevocationEndpoint
{
    /**
     * {@inheritdoc}
     */
    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        return $request->getParsedBody() ?? [];
    }
}
