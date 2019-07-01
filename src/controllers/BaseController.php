<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero\controllers;

use mediabeastnz\xero\Xero;
use mediabeastnz\xero\services\XeroAPIService;

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
            if ($order) {
                   
                $contact = Xero::$plugin->api->findOrCreateContact();
                if($contact){
                    $invoice = Xero::$plugin->api->createInvoice($contact, $order);
                    return "sent to xero";
                }

                return "not connected";
            }
        }
    }
    
}