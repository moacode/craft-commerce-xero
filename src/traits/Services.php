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
 * @author    Josh Smith <hey@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\xero\traits;

use Craft;
use craft\helpers\UrlHelper;

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\services\XeroAPIService;

/**
 * Services Trait
 *
 * @category Traits
 * @package  CraftCommerceXero
 * @author   Josh Smith <hey@joshthe.dev>
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

    // Private Methods
    // =========================================================================

    /**
     * Sets the plugin components
     *
     * @return void
     */
    private function _setPluginComponents()
    {
        $this->setComponents(
            [
            'api' => XeroAPIService::class,
            'xeroConnectionService' => XeroConnectionService::class,
            ]
        );
    }

    /**
     * Register dependencies using the global
     * dependency injection container for this plugin
     *
     * @return void
     */
    private function _registerDependencies()
    {
        $settings = $this->getSettings();

        $provider = new \Calcinai\OAuth2\Client\Provider\Xero(
            [
                'clientId'      => Craft::parseEnv($settings->xeroClientId),
                'clientSecret'  => Craft::parseEnv($settings->xeroClientSecret),
                'redirectUri'   => UrlHelper::cpUrl(Plugin::XERO_CALLBACK_ROUTE),
            ]
        );

        // All classes that have a typehinted constructor argument
        // of the Abstract Provider will receive a configured Xero Provider
        Craft::$container->set(
            'League\OAuth2\Client\Provider\AbstractProvider',
            $provider
        );
    }
}
