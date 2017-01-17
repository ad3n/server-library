<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\Model\Event;

final class EventId
{
    /**
     * @var string
     */
    protected $value;

    /**
     * EventId constructor.
     *
     * @param $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @param string $value
     *
     * @return EventId
     */
    public static function create(string $value): self
    {
        return new self($value);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->getValue();
    }
}
