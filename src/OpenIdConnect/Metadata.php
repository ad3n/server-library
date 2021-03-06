<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2\OpenIdConnect;

use Assert\Assertion;

class Metadata implements \JsonSerializable
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        Assertion::string($key);

        return array_key_exists($key, $this->values);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        Assertion::true($this->has($key), sprintf('Configuration value with key "%s" does not exist.', $key));

        return $this->values[$key];
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        Assertion::string($key);
        $this->values[$key] = $value;
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        if (true === $this->has($key)) {
            unset($this->values[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->values;
    }
}
