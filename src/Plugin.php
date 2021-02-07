<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2/3 plugin
 *
 * @author Myles Beardsmore <mediabeastnz@gmail.com>
 * @author Josh Smith <hey@joshthe.dev>
 *
 * @copyright 2019 Myles Derham
 * @link      https://www.mylesderham.dev/
 */

namespace thejoshsmith\xero;

use thejoshsmith\xero\jobs\SendToXeroJob;
use thejoshsmith\xero\models\Settings as SettingsModel;
use thejoshsmith\xero\traits\Routes;
use thejoshsmith\xero\traits\Services;
use thejoshsmith\xero\web\assets\SendToXeroAsset;

use Craft;
use craft\base\Plugin as CraftPlugin;
use craft\events\TemplateEvent;
use craft\web\View;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\commerce\elements\Order;

use yii\base\Event;

/**
 * Class Xero
 *
 * @package Xero
 * @author  Myles Derham <mediabeastnz@gmail.com>
 */
class Plugin extends CraftPlugin
{
    // Constants
    // =========================================================================

    /**
     * The plugin handle
     */
    const HANDLE = 'xero';

    /**
     * The default Xero OAuth callback route
     * used when redirecting back to Craft
     */
    const XERO_OAUTH_CALLBACK_ROUTE = 'xero/auth';

    /**
     * The default set of Xero OAuth grant permissions
     * the plugin will request from Xero
     */
    const XERO_OAUTH_SCOPES = 'openid email profile offline_access accounting.transactions';

    // Static Properties
    // =========================================================================

    /**
     * @var Plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.1.0';

    // Traits
    // =========================================================================

    use Routes;
    use Services;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Bootstrap the plugin
        $this->_setPluginComponents();
        $this->_setDependencies();
        $this->_registerEvents();
        $this->_registerCpRoutes();

        Craft::info(
            self::t(
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @param  $message
     * @param  array $params
     * @param  null  $language
     * @return string
     * @see    Craft::t()
     */
    public static function t($message, $params = [], $language = null)
    {
        return Craft::t(self::HANDLE, $message, $params, $language);
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): array
    {
        $ret = parent::getCpNavItem();

        $ret['label'] = self::t('Xero');

        if (Craft::$app->getUser()->checkPermission('xero-manageOrganisations')) {
            $ret['subnav']['organisation'] = [
                'label' => self::t('Organisation'),
                'url' => 'xero/organisation'
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $ret['subnav']['settings'] = [
            'label' => self::t('Settings'),
            'url' => 'xero/settings'
            ];
        }

        return $ret;
    }

    public function withDecimals($places = 4, $number)
    {
        return number_format((float)$number, $places, '.', '');
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the Settings Model for this plugin
     *
     * @return void
     */
    protected function createSettingsModel()
    {
        return new SettingsModel();
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers plugin events
     *
     * @return void
     */
    private function _registerEvents()
    {
        // Registers a CP URL used to send an order to Xero
        Event::on(
            UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
                $event->rules['sendordertoxero'] = 'xero/base/send-order-to-xero';
            }
        );

        // Loads additional JS into the commerce order edit screen
        Event::on(
            View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $event) {

                $view = Craft::$app->getView();

                // only run for CP requests
                if ($view->getTemplateMode() !== $view::TEMPLATE_MODE_CP ) {
                    return false;
                }

                // Only run on the entries edit template
                switch ($event->template) {
                case 'commerce/orders/_edit':

                    if ($event->variables['order']->isCompleted) {

                        if ($this->api->getInvoiceFromOrder($event->variables['order'])) {
                            $js = trim('var sentToXero = true');
                        } else {
                            $js = trim('var sentToXero = false');
                        }

                        if ($js) {
                            $view->registerJs($js, View::POS_END);
                        }
                        $view->registerAssetBundle(SendToXeroAsset::class);

                    }

                    break;
                }
            }
        );

        // Send completed and paid orders of to Xero (30 second delay)
        Event::on(
            Order::class, Order::EVENT_AFTER_ORDER_PAID, function (Event $e) {
                Craft::$app->queue->delay(30)->push(
                    new SendToXeroJob(
                        [
                        'orderID' => $e->sender->id
                        ]
                    )
                );
            }
        );
    }
}
