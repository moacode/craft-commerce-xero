<?php
/**
 * Services Trait
 *
 * Registers plugin services
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

namespace thejoshsmith\xero\traits;

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\factories\XeroClient as XeroClientFactory;
use thejoshsmith\xero\services\XeroAPI as XeroAPIService;
use thejoshsmith\xero\services\XeroOAuth as XeroOAuthService;
use thejoshsmith\xero\services\XeroConnections as XeroConnectionsService;

use Craft;
use craft\helpers\UrlHelper;
use yii\base\Exception;

/**
 * Services Trait
 *
 * @category Traits
 * @package  CraftCommerceXero
 * @author   Josh Smith <by@joshthe.dev>
 * @license  Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @link     https://joshthe.dev
 * @since    1.0.0
 */
trait Services
{
    /**
     * Returns the Xero API Service
     *
     * @return XeroAPIService
     */
    public function getXeroApi(): XeroAPIService
    {
        return $this->get('api');
    }

    /**
     * Returns the Xero OAuth Service
     *
     * @return XeroOAuthService
     */
    public function getXeroOAuth(): XeroOAuthService
    {
        return $this->get('oauth');
    }

    /**
     * Returns the Xero Connections Service
     *
     * @return XeroConnectionsService
     */
    public function getXeroConnections(): XeroConnectionsService
    {
        return $this->get('connections');
    }

    // Private Methods
    // =========================================================================

    /**
     * Sets the plugin components
     *
     * @return void
     */
    private function _setPluginComponents()
    {
        $settings = $this->getSettings();

        // Create an preconfigured instance of the Xero provider
        // to be injected into each instance of the api service
        $provider = new \Calcinai\OAuth2\Client\Provider\Xero(
            [
                'clientId' => Craft::parseEnv(
                    $settings->xeroClientId
                ),
                'clientSecret' => Craft::parseEnv(
                    $settings->xeroClientSecret
                ),
                'redirectUri' => UrlHelper::cpUrl(
                    Plugin::XERO_OAUTH_CALLBACK_ROUTE
                ),
            ]
        );

        $this->setComponents(
            [
                'api' => XeroAPIService::class,
                'oauth' => [
                    'class' => XeroOAuthService::class,
                    'scopes' => Plugin::XERO_OAUTH_SCOPES,
                    'provider' => $provider,
                ],
                'connections' => XeroConnectionsService::class
            ]
        );
    }

    /**
     * Set global dependency injection container definitions
     *
     * @return void
     */
    private function _setDependencies()
    {
        // Automatically inject an authenticated Xero Client
        // into the the consuming class.
        try {
            Craft::$container->set(
                'thejoshsmith\xero\models\XeroClient', XeroClientFactory::build()
            );
        } catch (Exception $e){
            // Swallow it whole
        }
    }
}
