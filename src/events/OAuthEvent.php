<?php

namespace thejoshsmith\xero\events;

use yii\base\Event;

/**
 * An OAuth event
 */
class OAuthEvent extends Event
{
    /**
     * Authorization code
     *
     * @var string
     */
    public $code;

    /**
     * An OAuth Access Token
     *
     * @var \League\OAuth2\Client\Token\AccessTokenInterface
     */
    public $token;

    /**
     * A decoded access token JWT
     *
     * @var object
     */
    public $jwt;

    /**
     * An array of connected tenants
     *
     * @var array
     */
    public $tenants;

    /**
     * Resource Owner
     *
     * @var ResourceOwner
     */
    public $resourceOwner;
}
