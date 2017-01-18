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

use Behat\Behat\Context\Context;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Factory\StreamFactoryInterface;
use OAuth2\Test\Application\Application;

class BaseContext implements Context
{
    /**
     * @var Application
     */
    private $application;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->application = new Application();
    }

    /**
     * @return Application
     */
    protected function getApplication(): Application
    {
        return $this->application;
    }

    /**
     * @return StreamFactoryInterface
     */
    protected function getStreamFactory(): StreamFactoryInterface
    {
        return $this->getApplication()->getStreamFactory();
    }

    /**
     * @return ServerRequestFactoryInterface
     */
    protected function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->getApplication()->getServerRequestFactory();
    }
}
