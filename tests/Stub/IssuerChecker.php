<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Stub;

use Jose\ClaimChecker\IssuerChecker as Base;

/**
 */
class IssuerChecker extends Base
{
    /**
     * {@inheritdoc}
     */
    protected function isIssuerAllowed($issuer)
    {
        return in_array($issuer, ['ISS1', 'ISS2']);
    }
}
