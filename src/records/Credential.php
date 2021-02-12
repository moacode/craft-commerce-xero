<?php

namespace thejoshsmith\xero\records;

use thejoshsmith\xero\Plugin;
use craft\db\ActiveRecord;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Active Record class for saving Xero Credentials
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Credential extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_credentials}}';
    }

    /**
     * Populates a Credential record with access token data
     *
     * @param AccessTokenInterface $accessToken AccessToken Object
     * @param self                 $credentials A credential record to populate
     *
     * @return self
     */
    public static function populateFromAccessToken(
        AccessTokenInterface $accessToken,
        Credential $credential = null
    ): Credential {

        if (empty($credential)) {
            $credential = new Credential;
        }

        $credential->accessToken = $accessToken->getToken();
        $credential->refreshToken = $accessToken->getRefreshToken();
        $credential->expires = date('Y-m-d H:i:s', $accessToken->getExpires());
        $credential->scope = $accessToken->getValues()['scope'];

        return $credential;
    }

    /**
     * Creates an Access Token from a credential record
     *
     * @param  Credential $credential Credential Record
     *
     * @return AccessToken
     */
    public static function toAccessToken(Credential $credential): AccessTokenInterface
    {
        $accessToken = new AccessToken([
            'access_token'      => $credential->accessToken,
            'refresh_token'     => $credential->refreshToken,
            'expires_in'        => strtotime($credential->expires)
        ]);

        return $accessToken;
    }

    public function rules()
    {
        return [
            [['id', 'accessToken', 'refreshToken', 'expires', 'scope'], 'safe'],
        ];
    }

    /**
     * Returns whether the access token has expired
     *
     * @return boolean
     */
    public function isExpired(): bool
    {
        return strtotime($this->expires) < time();
    }

    /**
     * Refreshes an access token and updates the record
     *
     * @return void
     */
    public function refreshAccessToken()
    {
        $xeroOAuth = Plugin::getInstance()->getXeroOAuth();
        $accessToken = $xeroOAuth->refreshAccessToken($this->refreshToken);

        // Update properties based on the new access token
        self::populateFromAccessToken($accessToken, $this);

        $this->save();

        return $this;
    }
}
