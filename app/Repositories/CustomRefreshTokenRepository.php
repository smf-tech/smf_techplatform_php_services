<?php

namespace App\Repositories;

use Laravel\Passport\Bridge\RefreshTokenRepository;

class CustomRefreshTokenRepository extends RefreshTokenRepository {
    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $refreshToken = $this->database->table('oauth_refresh_tokens')
                    ->where('id', $tokenId)->first();
        if ($refreshToken === null || $refreshToken['revoked']) {
            return true;
        }

        return $this->tokens->isAccessTokenRevoked(
            $refreshToken['access_token_id']
        );
    }
}
