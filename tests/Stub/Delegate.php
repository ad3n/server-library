<?php declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Test\Stub;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;

class Delegate implements DelegateInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback function (\Psr\Http\Message\RequestInterface $request) : \Psr\Http\Message\ResponseInterface
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param
     *
     * @return \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request)
    {
        return call_user_func($this->callback, $request);
    }
}
