<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @copyright 2019 Myles Derham
 * @link      https://www.mylesderham.dev/
 */

namespace thejoshsmith\xero\services;

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\records\InvoiceRecord;

use XeroPHP\Application as XeroApplication;

use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\Invoice\LineItem;
use XeroPHP\Models\Accounting\Payment;
use XeroPHP\Models\Accounting\Account;

use Craft;
use craft\base\Component;
use craft\commerce\elements\Order;
use XeroPHP\Remote\Collection;
use yii\base\Exception;

/**
 * @author  Myles Derham
 * @author  Josh Smith <by@joshthe.dev>
 * @package Xero
 */
class XeroAPI extends Component
{
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Defines the number of decimals to use
     *
     * @var integer
     */
    private $decimals = 2;

    // Public Methods
    // =========================================================================

    /**
     * Returns the Xero client connection
     *
     * @author Josh Smith <by@joshthe.dev>
     * @since  1.0.0
     *
     * @throws Exception
     * @return XeroApplication
     */
    public function getConnection(): XeroApplication
    {
        try {
            $xeroClient = Plugin::getInstance()
                ->getXeroOAuth()
                ->createClient();
        } catch (Exception $e) {
            throw new Exception('Unable to get Xero Client, check there\'s an active connection.');

            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }

        return $xeroClient;
    }

    public function sendOrder(Order $order)
    {
        if ($order) {
            // find or create the Contact
            $contact = $this->findOrCreateContact($order);
            if ($contact) {
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
            $contact = $this->connection->load('Accounting\\Contact')->where(
                '
                Name=="' . $user->getName() . '" OR
                EmailAddress=="' . $user->getName() . '"
            '
            )->first();
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
        } catch(\Exception $e) {
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
            } elseif ($adjustment->type !== 'tax') {
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

    public function getAccounts()
    {
        $cacheKey = 'xero-accounts';

        try {
            $connection = $this->getConnection();
            $cache = Craft::$app->getCache();

            $accounts = $this->_unserialize(Account::class, $cache->get($cacheKey));
            if (empty($accounts)) {
                $accounts = $connection->load(Account::class)->execute();
                $cache->set($cacheKey, $this->_serialize($accounts), self::CACHE_DURATION);
            }

        } catch(Exception $e) {
            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }

        return $accounts ?? [];
    }

    public function getAccountByCode($code)
    {
        try {
            $account = $this->getConnection()->load('Accounting\\Account')->where('Code=="' . $code . '"')->first();
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

    /**
     * Serializes a Xero collection of models
     * Note: this is necessary as each model has a reference to the
     * Guzzle client which contains anonymous functions, and therefore
     * we can't serialize it into cache "as is".
     *
     * @param Collection $collection A collection of models
     *
     * @see https://github.com/calcinai/xero-php/issues/734
     *
     * @return array
     */
    private function _serialize(Collection $collection): array
    {
        $serialized = [];

        foreach ($collection as $model) {
            $serialized[] = $model->toStringArray();
        }

        return $serialized;
    }

    /**
     * Used to unserialize cached data
     *
     * @param string $class      Name of the Xero API model class
     * @param array  $collection Collection of data to unserialize
     *
     * @see https://github.com/calcinai/xero-php/issues/734
     *
     * @return Collection|null
     */
    private function _unserialize(string $class, $collection): ?Collection
    {
        if (!is_iterable($collection)) {
            return null;
        }

        $unserialized = [];
        foreach ($collection as $data) {
            $model = new $class($this->getConnection());
            $model->fromStringArray($data);
            $unserialized[] = $model;
        }

        return new Collection($unserialized);
    }
}
