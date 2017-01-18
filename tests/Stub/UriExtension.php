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

namespace OAuth2\Test\Stub;

use Assert\Assertion;
use OAuth2\Response\Extension\ExtensionInterface;

final class UriExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(int $code, array $data): array
    {
        if ($code >= 200 && is_array($data) && array_key_exists('error', $data)) {
            $uri = sprintf('https://foo.test/Page/%d/%s', $code, $data['error']);
            Assertion::regex($uri, '/^[\x21\x23-\x5B\x5D-\x7E]+$/', 'Invalid URI.');
            $data['error_uri'] = $uri;
        }

        return $data;
    }
}
