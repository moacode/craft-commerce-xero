<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace thejoshsmith\xero\controllers;

use thejoshsmith\xero\Plugin;

use Craft;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;

class BaseController extends Controller
{
    /**
     * Initialises the base controller
     *
     * @return void
     */
    public function init()
    {
        $this->requirePermission('accessPlugin-xero');
        parent::init();
    }

    // Public Methods
    // =========================================================================

    public function actionSendOrderToXero()
    {
        $this->requireLogin();

        $orderId = Craft::$app->request->getParam('orderId');
        if ($orderId) {
            $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);
            Plugin::getInstance()->api->sendOrder($order);
        }
        return false;
    }

}
