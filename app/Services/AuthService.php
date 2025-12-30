<?php

namespace App\Services;

use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public static function checkAuth($token)
    {
        if (! $token) {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return false;
        }

        $user = $accessToken->tokenable;

        if (! $user) {
            return false;
        }

        return $user;
    }
}
