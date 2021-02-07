<?php

namespace thejoshsmith\xero\helpers;

use Firebase\JWT\JWT;

/**
 * Helper class for Xero related operations
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Xero
{
    /**
     * Returns a decoded Xero JWT
     *
     * @param string $token Access Token
     *
     * @return object
     */
    public static function decodeJwt(string $token): object
    {
        list($header, $body, $crypto) = explode('.', $token);
        return JWT::jsonDecode(JWT::urlsafeB64Decode($body));
    }
}
