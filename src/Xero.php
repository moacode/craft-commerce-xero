<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero;

use mediabeastnz\xero\services\XeroConnectionService;
use mediabeastnz\xero\services\XeroAPIService;
use mediabeastnz\xero\models\Settings;
use mediabeastnz\xero\web\assets\SendToXeroAsset;
use mediabeastnz\xero\behaviors\OrderBehavior;
use mediabeastnz\xero\elementactions\OrdersAction;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\TemplateEvent;
use craft\web\View;
use craft\events\RegisterUrlRulesEvent;
use craft\events\DefineBehaviorsEvent;
use craft\web\UrlManager;
use craft\events\RegisterElementActionsEvent;
use craft\commerce\elements\Order;

use yii\base\Event;

/**
 * Class Xero
 *
 * @author    Myles Derham
 * @package   Xero
 *
 * @property  XeroConnectionService $xeroConnectionService
 */
class Xero extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Xero
     */
    public static $plugin;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'xeroConnectionService' => XeroConnectionService::class,
            'api' => XeroAPIService::class,
        ]);

        Craft::info(
            Craft::t(
                'xero',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['sendordertoxero'] = 'xero/base/send-order-to-xero';
        });

        Event::on(Order::class, Order::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            $event->actions[] = OrdersAction::class;
        });

        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $event) {
            
            $view = Craft::$app->getView();

            // only run for CP requests
            if ( $view->getTemplateMode() !== $view::TEMPLATE_MODE_CP ) {
                return false;
            }

            // Only run on the entries edit template
            switch ($event->template) {
                case 'commerce/orders/_edit':

                     // only allow sending to Xero if:
                    // - order is completed
                    // - order isn't already in Xero
                    if ( $event->variables['order']->isCompleted == 0 ) {
                        exit;
                    }

                    $js = trim(
                        'var someData = "test"'."\r\n".
                        'var moreData = "test"'."\r\n"
                    );
                    if ($js) {
                        $view->registerJs($js, View::POS_END);
                    }
                    $view->registerAssetBundle(SendToXeroAsset::class);

                break;
            }
            
        });

    }

    public function withDecimals($places = 4, $number)
    {
        return number_format((float)$number, $places, '.', '');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'xero/settings',
            [
                'settings' => $this->getSettings(),
                'connection' => Xero::getInstance()->xeroConnectionService->setup()
            ]
        );
    }
    
}
