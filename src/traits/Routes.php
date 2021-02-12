<?php
/**
 * Routes Trait
 *
 * Registers custom control panel routes
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

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Routes Trait
 *
 * @category Traits
 * @package  CraftCommerceXero
 * @author   Josh Smith <by@joshthe.dev>
 * @license  Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @link     https://joshthe.dev
 * @since    1.0.0
 */
trait Routes
{
    // Private Methods
    // =========================================================================

    /**
     * Registers custom CP routes
     *
     * @return void
     */
    private function _registerCpRoutes()
    {
        Event::on(
            UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
                $event->rules['xero'] = ['template' => 'xero/index'];
                $event->rules['xero/organisation'] = 'xero/organisation/index';
                $event->rules['xero/connections/update'] = 'xero/connections/update';
                $event->rules['xero/connections/disconnect'] = 'xero/connections/disconnect';
                $event->rules['xero/settings'] = 'xero/settings/edit';
                $event->rules['xero/auth'] = 'xero/auth/index';
            }
        );
    }
}
