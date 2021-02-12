<?php

namespace thejoshsmith\xero\traits;

use Calcinai\OAuth2\Client\XeroResourceOwner;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Xero API Trait
 *
 * Provides methods to interface with the Xero OAuth API
 *
 * PHP version 7.4
 *
 * @category  Traits
 * @package   CraftCommerceXero
 * @author    Josh Smith <by@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

 /**
  * Xero API Trait
  *
  * @category Traits
  * @package  CraftCommerceXero
  * @author   Josh Smith <by@joshthe.dev>
  * @license  Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
  * @link     https://joshthe.dev
  * @since    1.0.0
  */
trait XeroOAuth
{
    /**
     * Define Xero OAuth permissions
     * These scopes are used to access the required
     * information from the connected tenants account
     */
    private $_scopes;

    /**
     * Stores the Xero Provider
     *
     * @var AbstractProvider
     */
    private $_provider;

    /**
     * Sets the Provider
     *
     * @param  AbstractProvider $provider An Abstract Provider (Guzzle)
     * @return void
     */
    public function setProvider(AbstractProvider $provider)
    {
        $this->_provider = $provider;
    }

    /**
     * Returns the Provider
     *
     * @return AbstractProvider
     */
    protected function getProvider(): AbstractProvider
    {
        return $this->_provider;
    }

    /**
     * Sets Xero scopes
     *
     * @param  string $scopes A list of Xero scopes
     * @return void
     */
    public function setScopes(string $scopes)
    {
        $this->_scopes = $scopes;
    }

    /**
     * Returns Xero scopes
     *
     * @return string
     */
    public function getScopes(): string
    {
        return $this->_scopes;
    }

    /**
     * Returns the OAuth state
     * 
     * @return array
     */
    public function getState(): string
    {
        return $this->getProvider()->getState();
    }

    /**
     * Returns an authorization URL
     *
     * @param  array $params
     * 
     * @return string
     */
    public function getAuthorizationUrl(array $params = []): string
    {
        return $this->getProvider()->getAuthorizationUrl($params);
    }

    /**
     * Returns an Access Token
     * 
     * @param  array  $params An array of params
     * 
     * @return AccessTokenInterface
     */
    public function getAccessToken(array $params = []): AccessTokenInterface
    {
        return $this->getProvider()->getAccessToken(
            'authorization_code', [
            'code' => $params['code']
            ]
        );
    }

    /**
     * Refreshes an access token
     *
     * @param string $refreshToken Refresh Token
     *
     * @return AccessTokenInterface
     */
    public function refreshAccessToken(string $refreshToken): AccessTokenInterface
    {
        return $this->getProvider()->getAccessToken(
            'refresh_token', [
            'refresh_token' => $refreshToken
            ]
        );
    }

    /**
     * Returns the authorised tenants
     * 
     * @param  AccessTokenInterface $accessToken Access Token
     * @param  array                $params      Request Params
     * 
     * @return array
     */
    public function getTenants(AccessTokenInterface $accessToken, array $params = null): array
    {
        return $this->getProvider()->getTenants($accessToken, $params);
    }

    /**
     * Returns the Resource Owner
     *
     * @param  AccessTokenInterface $accessToken Access Token
     * @param  array                $params      Request Params
     *
     * @return ResourceOwner
     */
    public function getResourceOwner(AccessTokenInterface $accessToken, array $params = null): XeroResourceOwner
    {
        return $this->getProvider()->getResourceOwner($accessToken, $params);
    }

    /**
     * Revokes a token from Xero
     *
     * @param AccessTokenInterface $accessToken  Access Token to revoke
     * @param string               $connectionId Connection ID
     *
     * @return void
     */
    public function disconnect(AccessTokenInterface $accessToken, string $connectionId)
    {
        return $this->getProvider()->disconnect($accessToken, $connectionId);
    }
}
