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

use thejoshsmith\xero\Xero;
use thejoshsmith\xero\services\XeroAPIService;

use Craft;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class BaseController extends Controller
{

    // Public Methods
    // =========================================================================

    public function actionSendOrderToXero()
    {
        $this->requireLogin();

        $orderId = Craft::$app->request->getParam('orderId');
        if ($orderId) {
            $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);
            Xero::$plugin->api->sendOrder($order);
        }
        return false;
    }

}
