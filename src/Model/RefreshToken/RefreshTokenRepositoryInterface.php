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

namespace OAuth2\Model\RefreshToken;

use OAuth2\Model\Client\Client;
use OAuth2\Model\ResourceOwner\ResourceOwner;

/**
 * @see    http://tools.ietf.org/html/rfc6749#section-6
 * @see    http://tools.ietf.org/html/rfc6749#section-1.5
 */
interface RefreshTokenRepositoryInterface
{
    /**
     * @param ResourceOwner      $resourceOwner
     * @param Client             $client
     * @param array              $parameters
     * @param \DateTimeImmutable $expiresAt
     * @param string[]           $scopes
     * @param array              $metadatas
     *
     * @return RefreshToken
     */
    public function create(ResourceOwner $resourceOwner, Client $client, array $parameters, \DateTimeImmutable $expiresAt, array $scopes, array $metadatas);

    /**
     * @param RefreshTokenId $refreshTokenId
     *
     * @return bool
     */
    public function has(RefreshTokenId $refreshTokenId): bool;

    /**
     * @param RefreshToken $refreshToken
     */
    public function save(RefreshToken $refreshToken);

    /**
     * @param RefreshTokenId $refreshTokenId Refresh token ID.
     *
     * @return RefreshToken|null
     *
     * @see     http://tools.ietf.org/html/rfc6749#section-6
     */
    public function find(RefreshTokenId $refreshTokenId);

    /**
     * Revoke a refresh token.
     *
     * @param RefreshToken $refreshToken The refresh token string to revoke.
     */
    public function revoke(RefreshToken $refreshToken);
}