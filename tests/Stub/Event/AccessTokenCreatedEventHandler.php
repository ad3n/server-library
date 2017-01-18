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

namespace OAuth2\Test\Stub\Event;

use OAuth2\Event\AccessToken\AccessTokenCreatedEvent;

final class AccessTokenCreatedEventHandler extends EventHandler
{
    /**
     * @param AccessTokenCreatedEvent $event
     */
    public function handle(AccessTokenCreatedEvent $event)
    {
        $this->save($event);
    }
}
