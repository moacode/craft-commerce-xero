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
            // grab the Order
            $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);
            if ($order) {
                // find or create the Contact
                $contact = Xero::$plugin->api->findOrCreateContact($order);
                if ($contact){ 
                    // create the Invoice
                    $invoice = Xero::$plugin->api->createInvoice($contact, $order);
                    // only continue to payment if a payment has been made and payments are enabled
                    if ($invoice && $order->isPaid && Xero::$plugin->getSettings()->createPayments) {
                        // before we can make the payment we need to get the Account
                        $account = Xero::$plugin->api->getAccountByCode(Xero::$plugin->getSettings()->accountReceivable);
                        if ($account) {
                            $payment = Xero::$plugin->api->createPayment($invoice, $account, $order);
                        }
                        return true;
                    }
                }
                return false;
            }
        }
    }
    
}