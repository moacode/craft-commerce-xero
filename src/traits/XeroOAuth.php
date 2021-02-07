<?php

namespace thejoshsmith\xero\traits;

use League\OAuth2\Client\Provider\AbstractProvider;

/**
 * Xero API Trait
 *
 * Provides methods to interface with the Xero OAuth API
 *
 * PHP version 7.4
 *
 * @category  Traits
 * @package   CraftCommerceXero
 * @author    Josh Smith <hey@joshthe.dev>
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
     * Stores the Access Token
     *
     * @var string
     */
    private $_accessToken;

    /**
     * ID of the connected tenant
     *
     * @var string
     */
    private $_tenantId;

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
    public function getProvider(): AbstractProvider
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
}
