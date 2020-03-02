<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero\services;

use mediabeastnz\xero\Xero;
use mediabeastnz\xero\records\InvoiceRecord;
use mediabeastnz\xero\models\InvoiceModel;

use XeroPHP\Application\PrivateApplication;
use XeroPHP\Remote\Exception\BadRequestException;
use XeroPHP\Remote\Exception\UnauthorizedException;
use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\Invoice\LineItem;
use XeroPHP\Models\Accounting\Payment;
use XeroPHP\Models\Accounting\Account;

use Craft;
use craft\commerce\elements\Order;
use craft\base\Component;
use yii\caching\Cache;

/**
 * @author    Myles Derham
 * @package   Xero
 */
class XeroAPIService extends Component
{

    private $connection;

    private $decimals = 2;

    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->connection = $this->getConnection();
    }

    public function getConnection()
    {
        // get the settings before checking connection
        $consumerKey = Xero::$plugin->getSettings()->consumerKey;
        $consumerSecret = Xero::$plugin->getSettings()->consumerSecret;
        $privateKeyPath = Xero::$plugin->getSettings()->privateKeyPath;
        $caBundlePath = Xero::$plugin->getSettings()->caBundlePath;
        $callbackUrl = Xero::$plugin->getSettings()->callbackUrl;

        // make sure consumer info is defined
        if (isset($consumerKey) && isset($consumerSecret) && isset($privateKeyPath) && isset($caBundlePath)) {
            
            // check for ca bundle
            if (!is_readable('file://'.CRAFT_BASE_PATH.'/'.$caBundlePath)) {
                return [
                    'message' => 'CA Bundle crt file can\'t be found.',
                    'code' => 404
                ];
            }

            // check for private key
            if (!is_readable('file://'.CRAFT_BASE_PATH.'/'.$privateKeyPath)) {
                return [
                    'message' => 'Private key can\'t be found.',
                    'code' => 404
                ];
            }

            // setup the request configuration
            $config = [
                'oauth' => [
                    'callback' => $callbackUrl,
                    'consumer_key' => $consumerKey,
                    'consumer_secret' => $consumerSecret,
                    'rsa_private_key' => 'file://'.CRAFT_BASE_PATH.'/'.$privateKeyPath,
                ],
                'curl' => array(
                    CURLOPT_CAINFO => CRAFT_BASE_PATH.'/'.$caBundlePath,
                ),
                'xero' => [
                    'unitdp' => 4
                ]
            ];

            $connection = new PrivateApplication($config);
            return $connection;
        }

        return false;
    }


    public function sendOrder(Order $order)
    {
        if ($order) {
            // find or create the Contact
            $contact = $this->findOrCreateContact($order);
            if ($contact){ 
                // create the Invoice
                $invoice = $this->createInvoice($contact, $order);
                // only continue to payment if a payment has been made and payments are enabled
                if ($invoice && $order->isPaid && Xero::$plugin->getSettings()->createPayments) {
                    // before we can make the payment we need to get the Account
                    $account = $this->getAccountByCode(Xero::$plugin->getSettings()->accountReceivable);
                    if ($account) {
                        $payment = $this->createPayment($invoice, $account, $order);

                    }
                    return true;
                }
            }
        }
        return false;
    } 


    public function findOrCreateContact(Order $order)
    {
        try {

            // this can return either fullname or their username (email hopefully)
            $user = $order->getUser();
            $contact = $this->connection->load('Accounting\\Contact')->where('
                Name=="' . $user->getName() . '" OR
                EmailAddress=="' . $user->getName() . '"
            ')->first();
            if (empty($contact) && !isset($contact)) {
                $contact = new Contact($this->connection);
                $contact->setName($user->getName())
                        ->setFirstName($user->firstName)
                        ->setLastName($user->lastName)
                        ->setEmailAddress($user->email);
                    
                // TODO: add hook (before_save_contact)
                        
                $contact->save();       
            }
            return $contact;
        } catch(Exception $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];

            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }
        return false;
    }


    public function createInvoice(Contact $contact, Order $order)
    {
        $invoice = new Invoice($this->connection);
        // get line items
        foreach ($order->getLineItems() as $orderItem) {
            $lineItem = new LineItem($this->connection);
            $lineItem->setAccountCode(Xero::$plugin->getSettings()->accountSales);
            $lineItem->setDescription($orderItem->description);
            $lineItem->setQuantity($orderItem->qty);
            if ($orderItem->discount > 0) {
                $discountPercentage = (($orderItem->discount / $orderItem->subtotal) * -100);
                $lineItem->setDiscountRate(Xero::$plugin->withDecimals($this->decimals, $discountPercentage));
            }
            if ($orderItem->salePrice > 0) {
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $orderItem->salePrice));
            } else {
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $orderItem->price));
            }

            // TODO: check for line item adjustments


            // check if product codes should be used and sent (inventory updates)
            if (Xero::$plugin->getSettings()->updateInventory) {
                $lineItem->setItemCode($orderItem->sku);
            }

            $invoice->addLineItem($lineItem);
        }      

        // get all adjustments (discounts,shipping etc)
        $adjustments = $order->getOrderAdjustments();
        foreach ($adjustments as $adjustment) {
            // shipping adjustments
            if ($adjustment->type == 'shipping') {
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode(Xero::$plugin->getSettings()->accountShipping);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $order->getTotalShippingCost()));
                $invoice->addLineItem($lineItem);    
            } elseif ($adjustment->type == 'discount' ) {
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode(Xero::$plugin->getSettings()->accountDiscount);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $adjustment->amount));
                $invoice->addLineItem($lineItem);
            } elseif ($adjustment->type !== 'tax'){
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode(Xero::$plugin->getSettings()->accountAdditionalFees);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $adjustment->amount));
                $invoice->addLineItem($lineItem);
            }
        }
                
        // setup invoice
        $invoice->setStatus('AUTHORISED')
                ->setType('ACCREC')
                ->setContact($contact)
                ->setLineAmountType("Exclusive") // TODO: this should be optional (Inclusive/Exclusive)
                ->setCurrencyCode($order->getPaymentCurrency())
                ->setInvoiceNumber($order->reference)
                ->setSentToContact(true)
                ->setDueDate(new \DateTime('NOW'));

        // TODO: add hook (before_invoice_save)

        try {
            // save the invoice
            $invoice->save();            

            // Would $orderTotal ever be more than $invoice->Total?
            // If so, what should happen with rounding?
            $orderTotal = Xero::$plugin->withDecimals($this->decimals, $order->getTotalPrice());
            if ($invoice->Total > $orderTotal) {
                
                // caclulate how much rounding to adjust
                $roundingAdjustment = $orderTotal - $invoice->Total;
                $roundingAdjustment = Xero::$plugin->withDecimals($this->decimals, $roundingAdjustment);
                
                // add rounding to invoice
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode(Xero::$plugin->getSettings()->accountRounding);
                $lineItem->setDescription("Rounding adjustment: Order Total: $".$orderTotal);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount($roundingAdjustment);
                $invoice->addLineItem($lineItem);
                
                // update the invoice with new rounding adjustment
                $invoice->save();
            }   

            $invoiceRecord = new InvoiceRecord();
            $invoiceRecord->orderId = $order->id;
            $invoiceRecord->invoiceId = $invoice->InvoiceID;
            $invoiceRecord->save();

            // TODO: add hook (after_invoice_save)

            return $invoice;

        } catch(Exception $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];

            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }

        return false;

    }


    public function createPayment(Invoice $invoice, Account $account, Order $order)
    {
        try {
            // create the payment
            $payment = new Payment($this->connection);
            $payment->setInvoice($invoice)
                    ->setAccount($account)
                    ->setReference($order->getLastTransaction()->reference)
                    ->setAmount(Xero::$plugin->withDecimals($this->decimals, $order->getTotalPaid()))
                    ->setDate($order->datePaid);
            $payment->save();
            return $payment;
        } catch(Exception $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];

            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }
        return false;
    }


    public function getAccountByCode($code)
    {
        try {
            $account = $this->connection->load('Accounting\\Account')->where('Code=="' . $code . '"')->first();
            return $account;
        } catch(Exception $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];

            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }
        return false;
    }


    public function getInvoiceFromOrder(Order $order)
    {
        $invoice = InvoiceRecord::find()->where(['orderId' => $order->id])->one();
        if ($invoice && isset($invoice->invoiceId)) {
            return true;
        }
        return false;
    }

}
